<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use ArPHP\I18N\Arabic;

/**
 * Renders a point-in-time PDF snapshot of an Energy Isolation license or
 * Hot Work Permit, mirroring the on-screen view pages, for attaching to
 * workflow notification emails.
 *
 * dompdf has no bidi/Arabic-shaping engine of its own (confirmed: no bidi
 * reordering or contextual glyph-joining logic anywhere in its source), so
 * every piece of text is reshaped through ArPHP's utf8Glyphs() before being
 * placed in the HTML — it converts logically-ordered Arabic (optionally
 * mixed with Latin/numeric fragments) into the pre-joined, pre-reordered
 * visual-order string that a plain left-to-right glyph renderer needs to
 * display it correctly. Each label+value or prefix+value pair is combined
 * into a single logical string before reshaping so the whole phrase is
 * reordered as one unit instead of two independently-reshaped fragments.
 *
 * The same "dompdf can't be trusted to do RTL on its own" rule applies to
 * multi-column layout: `dir="rtl"` / `direction: rtl` table-column mirroring
 * is unreliable across renderers, so every multi-column table/row here is
 * built as a plain left-to-right table with its cells placed in the exact
 * DOM order needed to already look right — first logical field emitted
 * LAST so it lands in the rightmost cell under ordinary LTR flow.
 */
class LicensePdfController
{
    private const BRAND_COLOR = '#0b6f76';

    private static ?Arabic $arabicShaper = null;

    public static function generateEnergyIsolationPdf(PDO $conn, int $licenseId): string
    {
        $stmt = $conn->prepare("
            SELECT l.*, u.name AS creator_name, u.signature AS creator_signature, am.name AS area_manager_name,
                   off.name AS official_name, off.department AS official_department,
                   es.name AS section_name
            FROM energy_insulation_license l
            LEFT JOIN users u ON l.created_by = u.id
            LEFT JOIN users am ON l.area_manager_id = am.id
            LEFT JOIN equipment_sections es ON l.equipment_section_id = es.id
            LEFT JOIN energy_insulation_officials off ON l.id = off.license_id
            WHERE l.id = ?
        ");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$license) {
            throw new Exception('License not found for PDF generation');
        }

        $energyStmt = $conn->prepare("
            SELECT et.id, et.name
            FROM energy_insulation_energy_types liet
            JOIN energy_types et ON liet.energy_type_id = et.id
            WHERE liet.license_id = ?
        ");
        $energyStmt->execute([$licenseId]);
        $energyTypes = $energyStmt->fetchAll(PDO::FETCH_ASSOC);

        $equipStmt = $conn->prepare("
            SELECT e.id, e.name, e.image, lie.equipment_no
            FROM energy_insulation_equipments lie
            JOIN equipments e ON lie.equipment_id = e.id
            WHERE lie.license_id = ?
        ");
        $equipStmt->execute([$licenseId]);
        $equipments = $equipStmt->fetchAll(PDO::FETCH_ASSOC);

        $staffStmt = $conn->prepare("
            SELECT s.name, sg.name as group_name, sg.id as group_id, IFNULL(sg.is_done, 0) as group_is_done
            FROM energy_insulation_staff s
            LEFT JOIN energy_insulation_staff_group sg ON s.group_id = sg.id
            WHERE s.license_id = ?
        ");
        $staffStmt->execute([$licenseId]);
        $staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        $status = $license['status'] ?? 'pending';
        $effectiveStatus = self::energyEffectiveStatus($status, $license['license_expiry'] ?? null);

        $body = self::header('رخصة عزل الطاقة - Energy Insulation License');
        $body .= "<div style='text-align:left; margin-bottom:10px;'>" . self::statusBadge(self::energyStatusText($effectiveStatus), self::energyStatusClass($effectiveStatus)) . "</div>";

        $officialLabel = trim(($license['official_name'] ?? '') . (!empty($license['official_department']) ? ' (' . $license['official_department'] . ')' : ''));
        $pairs = [
            ['رقم الرخصة', $license['equipment_no']],
            ['التاريخ', $license['date']],
            ['الموقع', $license['exact_location']],
            ['اسم المعدة', $license['equipment_name']],
            ['القسم', $license['section_name']],
            ['السبب', $license['reason']],
            ['تصريح العمل', $license['work_permit']],
            ['مرخص العزل', $license['creator_name']],
            ['طالب العزل', $license['requester_name']],
            ['مسؤول المنطقة', $license['area_manager_name']],
            ['اسم العازل', $officialLabel],
        ];
        if (!empty($license['am_approved_at'])) {
            $pairs[] = ['تاريخ تأكيد العزل', $license['am_approved_at']];
        }
        $body .= "<div class='card'>" . self::title('معلومات عامة') . self::infoGrid($pairs) . "</div>";

        $body .= "<div class='card'>" . self::title('أنواع الطاقة المعزولة');
        if (empty($energyTypes)) {
            $body .= "<p class='label'>" . self::esc('لا توجد أنواع طاقة') . "</p>";
        } else {
            foreach (array_reverse($energyTypes) as $et) {
                $body .= "<span class='tag'>" . self::esc($et['name']) . "</span>";
            }
        }
        $body .= "</div>";

        $body .= "<div class='card'>" . self::title('اسم المعدة المراد عزلها');
        if (empty($equipments)) {
            $body .= "<p class='label'>" . self::esc('لا توجد معدات') . "</p>";
        } else {
            $body .= "<table class='list'><thead><tr><th>" . self::esc('الرقم المرجعي') . "</th><th>" . self::esc('الاسم') . "</th><th>" . self::esc('الصورة') . "</th></tr></thead><tbody>";
            foreach ($equipments as $eq) {
                $imgPath = self::assetPath($eq['image'] ?? null);
                $imgHtml = $imgPath ? "<img src='{$imgPath}' style='height:32px;width:32px;object-fit:cover;'>" : '-';
                $body .= "<tr><td>" . self::esc($eq['equipment_no']) . "</td><td>" . self::esc($eq['name']) . "</td><td>{$imgHtml}</td></tr>";
            }
            $body .= "</tbody></table>";
        }
        $body .= "</div>";

        $body .= "<div class='card'>" . self::title('طاقم العمل');
        if (empty($staff)) {
            $body .= "<p class='label'>" . self::esc('لا يوجد طاقم عمل مسجل') . "</p>";
        } else {
            $groups = [];
            foreach ($staff as $member) {
                $gName = $member['group_name'] ?: 'طاقم العمل';
                if (!isset($groups[$gName])) {
                    $groups[$gName] = ['members' => [], 'is_done' => (int)($member['group_is_done'] ?? 0)];
                }
                $groups[$gName]['members'][] = $member['name'];
            }
            foreach ($groups as $gName => $g) {
                $cls = $g['is_done'] ? 'group done' : 'group';
                // Reshape the group name alone, then append the checkmark (not Arabic script, left untouched by reshaping).
                $titleHtml = self::esc($gName) . ($g['is_done'] ? ' &#10004;' : '');
                $body .= "<div class='{$cls}'><div class='group-title'>{$titleHtml}</div>";
                foreach (array_reverse($g['members']) as $m) {
                    $body .= "<span class='tag'>" . self::esc($m) . "</span>";
                }
                $body .= "</div>";
            }
        }
        $body .= "</div>";

        if (in_array($status, ['active_isolation', 'completed'], true)) {
            $sigPath = self::assetPath($license['creator_signature'] ?? null);
            $body .= "<div class='card' style='border-top:4px solid #16a34a;'>" . self::title('تم العزل', '#15803d');
            $body .= "<p>" . self::esc('تمت المصادقة على العزل بواسطة مرخص العزل') . "</p>";
            $body .= "<p class='value'>" . self::esc($license['creator_name']) . "</p>";
            if ($sigPath) {
                $body .= "<img class='signature' src='{$sigPath}' alt='signature'>";
            }
            $body .= "</div>";
        }

        if ($status === 'rejected' && !empty($license['reject_reason'])) {
            $body .= "<div class='card' style='border-top:4px solid #dc2626;'>" . self::title('رخصة مرفوضة', '#b91c1c');
            $body .= "<p>" . nl2br(self::esc($license['reject_reason'])) . "</p></div>";
        }

        if ($status === 'completed') {
            $sigPath = self::assetPath($license['creator_signature'] ?? null);
            $body .= "<div class='card'>" . self::title('طلب رفع العزل - ' . ($license['creator_name'] ?? ''));
            $body .= "<p>" . self::esc('تم الانتهاء من العمل على المعده وتم ازالة كافة الاقفال الشخصية الخاصه للمجموعه وتم تنصيب كافة الواقيات وتنظيف المكان') . "</p>";
            $body .= "<p>" . self::esc('تمت المصادقة على رفع العزل بواسطة: ' . ($license['requester_name'] ?? '')) . "</p>";
            if (!empty($license['end_at'])) {
                $body .= "<p>" . self::esc('تاريخ رفع العزل: ' . $license['end_at']) . "</p>";
            }
            if ($sigPath) {
                $body .= "<img class='signature' src='{$sigPath}' alt='signature'>";
            }
            $body .= "</div>";
        }

        $body .= self::footerNote();

        return self::renderPdf($body, 'Energy Isolation License #' . $licenseId);
    }

    public static function generateHotWorkPermitPdf(PDO $conn, int $permitId): string
    {
        $stmt = $conn->prepare("SELECT h.*, u.name as assigned_to_name, c.name as creator_name,
                                        cm.name as critical_manager_name, cs.name as critical_supervisor_name,
                                        ft.name as finishing_time_updated_by, sr.name as safety_reviewer_name,
                                        sr.signature as safety_reviewer_signature, h.safety_reviewed_at
                                FROM hot_work_permit h
                                LEFT JOIN users u ON h.assigned_to = u.id
                                LEFT JOIN users c ON h.created_by = c.id
                                LEFT JOIN users cm ON h.critical_manager_id = cm.id
                                LEFT JOIN users cs ON h.critical_supervisor_id = cs.id
                                LEFT JOIN users ft ON h.finishing_time_updated_by = ft.id
                                LEFT JOIN users sr ON h.safety_reviewer_id = sr.id
                                WHERE h.id = ?");
        $stmt->execute([$permitId]);
        $permit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$permit) {
            throw new Exception('Permit not found for PDF generation');
        }

        $stmtEquip = $conn->prepare("SELECT equipment_name FROM hot_work_equipment_used WHERE hot_work_permit_id = ?");
        $stmtEquip->execute([$permitId]);
        $equipmentList = $stmtEquip->fetchAll(PDO::FETCH_COLUMN);

        $stmtAdd = $conn->prepare("SELECT * FROM additional_hot_permits WHERE hot_work_permit_id = ?");
        $stmtAdd->execute([$permitId]);
        $additionalPermits = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);

        $stmtControl = $conn->prepare("SELECT * FROM hot_work_control_measures WHERE hot_work_permit_id = ?");
        $stmtControl->execute([$permitId]);
        $controlMeasures = $stmtControl->fetchAll(PDO::FETCH_ASSOC);

        $stmtPerf = $conn->prepare("SELECT * FROM hot_work_performers_check WHERE hot_work_permit_id = ?");
        $stmtPerf->execute([$permitId]);
        $performersCheck = $stmtPerf->fetchAll(PDO::FETCH_ASSOC);

        $stmtAppr = $conn->prepare("SELECT * FROM hot_permit_approvals WHERE hot_work_permit_id = ?");
        $stmtAppr->execute([$permitId]);
        $approvals = $stmtAppr->fetchAll(PDO::FETCH_ASSOC);

        $isClosed = !empty($permit['done_at']) && (empty($permit['finishing_time']) || strtotime($permit['done_at']) <= strtotime($permit['finishing_time']));
        $isOpen = !$isClosed && (empty($permit['finishing_time']) || strtotime($permit['finishing_time']) >= time());
        if ($isClosed) {
            $statusText = 'مغلقة - Close';
            $statusCls = 'badge-green';
        } elseif ($isOpen) {
            $statusText = 'مفتوحة - Open';
            $statusCls = 'badge-blue';
        } else {
            $statusText = 'غير نشطة - Not Active';
            $statusCls = 'badge-red';
        }

        $body = self::header('رخصة العمل الساخن - Hot Work Permit');
        $body .= "<div style='text-align:left; margin-bottom:10px;'>" . self::statusBadge($statusText, $statusCls) . "</div>";

        $equipmentText = !empty($equipmentList) ? implode('، ', $equipmentList) : '-';
        $permitType = ((int)($permit['is_critical'] ?? 0) === 1) ? 'رخصة العمل الساخن (الحرجة)' : 'رخصة العمل الساخن (عاديه)';
        $pairs = [
            ['رقم الرخصة', $permit['permit_no']],
            ['تاريخ الإصدار', $permit['issuing_date_time']],
            ['اسم طالب الرخصه', $permit['company_name']],
            ['القسم', $permit['location']],
            ['الموقع الدقيق', $permit['supervisor']],
            ['المعدة المستخدمة', $equipmentText],
            ['تاريخ اصدار الرخصه', $permit['task_start_datetime']],
            ['وقت انتهاء الرخصه', $permit['finishing_time']],
            ['تم الإنشاء بواسطة', $permit['creator_name']],
            ['مسند إلى', $permit['assigned_to_name']],
            ['رقم أمر العمل (WO)', $permit['WO'] ?? null],
            ['نوع الرخصة', $permitType],
        ];
        $body .= "<div class='card'>" . self::title('المعلومات الأساسية') . self::infoGrid($pairs) . "</div>";

        if (($permit['safety_status'] ?? '') === 'approved') {
            $sigPath = self::assetPath($permit['safety_reviewer_signature'] ?? null);
            $body .= "<div class='card'>" . self::title('معلومات موافقة قسم السلامة');
            $body .= "<table class='grid'><tr>" . self::infoRow('وقت الموافقة', $permit['safety_reviewed_at']) . self::infoRow('اسم السلامة الموافق', $permit['safety_reviewer_name']) . "</tr></table>";
            if ($sigPath) {
                $body .= "<img class='signature' src='{$sigPath}' alt='signature'>";
            }
            $body .= "</div>";
        } elseif (($permit['safety_status'] ?? '') === 'rejected' && !empty($permit['safety_comment'])) {
            $body .= "<div class='card' style='border-top:4px solid #dc2626;'>" . self::title('سبب الرفض', '#b91c1c');
            $body .= "<p>" . nl2br(self::esc($permit['safety_comment'])) . "</p></div>";
        }

        if (!empty($additionalPermits)) {
            $body .= "<div class='card'>" . self::title('التصاريح الإضافية المرفقة');
            $body .= "<table class='list'><thead><tr><th>" . self::esc('رقم التصريح') . "</th><th>" . self::esc('اسم التصريح') . "</th></tr></thead><tbody>";
            foreach ($additionalPermits as $ap) {
                $body .= "<tr><td>" . self::esc($ap['permit_number']) . "</td><td>" . self::esc($ap['permit_name']) . "</td></tr>";
            }
            $body .= "</tbody></table>";
            if (!empty($permit['work_description'])) {
                $body .= "<p style='margin-top:8px;'>" . self::esc('وصف العمل:') . "<br>" . nl2br(self::esc($permit['work_description'])) . "</p>";
            }
            $body .= "</div>";
        } elseif (!empty($permit['work_description'])) {
            $body .= "<div class='card'>" . self::title('وصف العمل') . "<p>" . nl2br(self::esc($permit['work_description'])) . "</p></div>";
        }

        if (!empty($controlMeasures)) {
            $body .= "<div class='card'>" . self::title('إجراءات السيطرة') . "<table class='list'><tbody>";
            foreach ($controlMeasures as $i => $cm) {
                $body .= "<tr><td style='width:70px; text-align:center;'>" . self::esc($cm['status']) . "</td><td>" . self::esc(($i + 1) . '. ' . $cm['measure_text']) . "</td></tr>";
            }
            $body .= "</tbody></table></div>";
        }

        if (!empty($performersCheck)) {
            $body .= "<div class='card'>" . self::title('منفذي الأعمال الساخنة') . "<table class='list'><tbody>";
            foreach ($performersCheck as $i => $pc) {
                $body .= "<tr><td style='width:70px; text-align:center;'>" . self::esc($pc['answer']) . "</td><td>" . self::esc(($i + 1) . '. ' . $pc['question_text']) . "</td></tr>";
            }
            $body .= "</tbody></table></div>";
        }

        if (!empty($approvals)) {
            $body .= "<div class='card'>" . self::title('المطابقة والموافقة') . "<table class='list'><thead><tr><th>" . self::esc('الحالة') . "</th><th>" . self::esc('الاسم') . "</th><th>" . self::esc('الدور') . "</th></tr></thead><tbody>";
            foreach ($approvals as $app) {
                $isApproved = strpos($app['approval_status'] ?? '', 'Approved') !== false;
                $parts = explode(' - ', $app['approval_status'] ?? '');
                $name = $parts[0] ?? 'N/A';
                $statusLabel = $isApproved ? 'تمت الموافقة' : 'قيد الانتظار';
                $body .= "<tr><td>" . self::esc($statusLabel) . "</td><td>" . self::esc($name) . "</td><td>" . self::esc($app['role_name']) . "</td></tr>";
            }
            $body .= "</tbody></table></div>";
        }

        $body .= self::footerNote();

        return self::renderPdf($body, 'Hot Work Permit #' . $permitId);
    }

    private static function energyEffectiveStatus(?string $status, ?string $expiry): string
    {
        if ($status === 'completed') {
            return 'close';
        }
        if ($status === 'active_isolation') {
            if (!empty($expiry) && strtotime($expiry) < time()) {
                return 'not_active';
            }
            return 'open';
        }
        return $status ?? 'pending';
    }

    private static function energyStatusText(string $status): string
    {
        $map = [
            'pending' => 'بانتظار الموافقة',
            'open' => 'تم العزل - Isolation Active',
            'not_active' => 'غير نشط - Not Active',
            'close' => 'مكتملة - Isolation Removed',
            'rejected' => 'مرفوضة',
        ];
        return $map[$status] ?? $status;
    }

    private static function energyStatusClass(string $status): string
    {
        $map = [
            'pending' => 'badge-yellow',
            'open' => 'badge-blue',
            'not_active' => 'badge-red',
            'close' => 'badge-green',
            'rejected' => 'badge-red',
        ];
        return $map[$status] ?? 'badge-yellow';
    }

    private static function statusBadge(string $text, string $cls): string
    {
        return "<span class='badge {$cls}'>" . self::esc($text) . "</span>";
    }

    private static function header(string $titleAr): string
    {
        $logo = self::logoPath();
        return "<div class='header'>" . ($logo ? "<img src='{$logo}' alt='logo'>" : '') . "<h1>" . self::esc($titleAr) . "</h1></div>";
    }

    /**
     * A card section title, reshaped as one unit.
     */
    private static function title(string $text, ?string $color = null): string
    {
        $style = $color ? " style='color:{$color};'" : '';
        return "<div class='card-title'{$style}>" . self::esc($text) . "</div>";
    }

    /**
     * One "label: value" cell, combined into a single logical string before
     * reshaping so the Arabic label and the (possibly Latin/mixed) value are
     * reordered together as one bidi unit rather than as two independently
     * reshaped fragments that dompdf would then place in raw source order.
     */
    private static function infoRow(string $label, $value): string
    {
        $displayValue = ($value !== null && $value !== '') ? $value : '-';
        return "<td>" . self::esc($label . ': ' . $displayValue) . "</td>";
    }

    private static function infoGrid(array $pairs): string
    {
        $html = "<table class='grid'>";
        foreach (array_chunk($pairs, 2) as $chunk) {
            // Reversed on purpose: under plain left-to-right table layout the
            // first cell lands leftmost, but the first field in $pairs is the
            // one that should read rightmost — so it's emitted last.
            $chunk = array_reverse($chunk);
            if (count($chunk) === 1) {
                $html .= "<tr><td></td>" . self::infoRow($chunk[0][0], $chunk[0][1]) . "</tr>";
                continue;
            }
            $html .= "<tr>";
            foreach ($chunk as $p) {
                $html .= self::infoRow($p[0], $p[1]);
            }
            $html .= "</tr>";
        }
        $html .= "</table>";
        return $html;
    }

    private static function footerNote(): string
    {
        return "<p class='footer-note'>" . self::esc('تم إنشاء هذا المستند تلقائيًا بتاريخ ' . date('Y-m-d H:i')) . "</p>";
    }

    private static function arabicShaper(): Arabic
    {
        if (self::$arabicShaper === null) {
            self::$arabicShaper = new Arabic();
        }
        return self::$arabicShaper;
    }

    /**
     * Reshapes Arabic text (joining forms + visual reordering, leaving any
     * Latin/numeric fragments in place) and HTML-escapes the result. dompdf
     * cannot do this itself, so every string rendered into the PDF must pass
     * through here rather than a plain htmlspecialchars().
     */
    private static function esc($value): string
    {
        $text = (string)($value ?? '');
        if ($text === '') {
            return '';
        }
        // A large $max_chars disables the library's internal line-wrapping (meant for
        // fixed-width GD/image rendering); our HTML/CSS wraps naturally on its own, and
        // letting the library hard-wrap here was corrupting some longer mixed strings.
        // $hindo=false keeps Western digits, matching how dates/numbers already look
        // throughout the rest of the app.
        $text = self::arabicShaper()->utf8Glyphs($text, 10000, false);
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private static function logoPath(): ?string
    {
        return self::assetPath('images/logo.png');
    }

    /**
     * Resolves a DB-stored relative path (e.g. "uploads/equipments/x.png") to an
     * absolute local filesystem path under /public so dompdf can embed it directly,
     * without ever fetching a remote URL.
     */
    private static function assetPath(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }
        $publicRoot = realpath(__DIR__ . '/../public');
        if (!$publicRoot) {
            return null;
        }
        $candidate = realpath($publicRoot . '/' . ltrim($relativePath, '/'));
        if (!$candidate || strpos($candidate, $publicRoot) !== 0) {
            return null;
        }
        return str_replace('\\', '/', $candidate);
    }

    private static function renderPdf(string $bodyHtml, string $title): string
    {
        $fontDir = realpath(__DIR__ . '/../public/fonts');

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('fontDir', $fontDir);
        $options->set('fontCache', $fontDir);
        $options->set('chroot', realpath(__DIR__ . '/..'));
        $options->set('defaultFont', 'Amiri');

        $dompdf = new Dompdf($options);

        $fontMetrics = $dompdf->getFontMetrics();
        $fontMetrics->registerFont(['family' => 'Amiri', 'weight' => 'normal', 'style' => 'normal'], $fontDir . '/Amiri-Regular.ttf');
        $fontMetrics->registerFont(['family' => 'Amiri', 'weight' => 'bold', 'style' => 'normal'], $fontDir . '/Amiri-Bold.ttf');

        $css = self::baseCss();
        // No dir="rtl" here on purpose — dompdf's table-column mirroring for it proved
        // unreliable across real-world viewers, so every multi-column construct above
        // is pre-arranged in plain left-to-right DOM order to already look correct.
        $html = "<!DOCTYPE html><html lang=\"ar\"><head><meta charset=\"UTF-8\"><title>" . self::esc($title) . "</title><style>{$css}</style></head><body>{$bodyHtml}</body></html>";

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private static function baseCss(): string
    {
        $brand = self::BRAND_COLOR;
        return "
            body { font-family: 'Amiri', sans-serif; text-align: right; font-size: 11pt; color: #1f2937; }
            .header { text-align: center; border-bottom: 2px solid {$brand}; padding-bottom: 8px; margin-bottom: 16px; }
            .header img { height: 40px; }
            .header h1 { font-size: 16pt; color: {$brand}; margin: 6px 0 0; }
            .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; margin-bottom: 14px; page-break-inside: avoid; }
            .card-title { font-size: 13pt; font-weight: bold; color: {$brand}; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 10px; }
            table.grid { width: 100%; border-collapse: collapse; }
            table.grid td { padding: 4px 6px; font-size: 10.5pt; vertical-align: top; width: 50%; }
            .label { color: #6b7280; }
            .value { font-weight: bold; color: #111827; }
            table.list { width: 100%; border-collapse: collapse; margin-top: 4px; }
            table.list th, table.list td { border: 1px solid #e5e7eb; padding: 5px 8px; font-size: 10pt; }
            table.list th { background: #f9fafb; }
            .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 9.5pt; font-weight: bold; }
            .badge-green { background: #d1fae5; color: #065f46; }
            .badge-blue { background: #dbeafe; color: #1e40af; }
            .badge-yellow { background: #fef3c7; color: #92400e; }
            .badge-red { background: #fee2e2; color: #991b1b; }
            .tag { display: inline-block; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 2px 10px; margin: 2px; font-size: 9.5pt; }
            .group { background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; }
            .group.done { background: #ecfdf5; border-color: #a7f3d0; }
            .group-title { font-weight: bold; color: {$brand}; font-size: 10.5pt; margin-bottom: 4px; }
            .signature { height: 50px; margin-top: 4px; }
            .footer-note { color: #9ca3af; font-size: 8.5pt; text-align: center; margin-top: 20px; }
        ";
    }
}
