<?php
require_once '../core/Database.php';
require_once '../config/config.php';

require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/partials/navbar.php';

require_once 'helpers/authCheck.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>KCML / SLV | Action</title>
</head>

<body>

    <?php renderNavbar('Action'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar(''); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <!-- Action detail card (replace $SELECTION_PLACEHOLDER$ with this) -->
                <div class="max-w-6xl mx-auto">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h1 id="title" class="text-2xl sm:text-3xl font-semibold text-slate-800">Action Details</h1>
                            <p id="category" class="mt-3 text-slate-600 leading-relaxed">category</p>
                            <p id="type" class="mt-3 text-slate-600 leading-relaxed text-sm">type</p>
                        </div>
                    </div>

                    <!-- Grid: left media, right details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left: media previews (image + PDF preview) -->
                        <div class="md:col-span-1 space-y-4">
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-slate-100">
                                <div class="h-56 w-full bg-slate-50 flex items-center justify-center">
                                    <img id="action_image"
                                        src="https://via.placeholder.com/800x450.png?text=Action+Image"
                                        alt="Action image" class="object-cover">
                                </div>
                                <div class="p-3 border-t border-slate-100">
                                    <p class="text-sm text-slate-600">Main image for the action.</p>
                                    <div class="mt-3 flex gap-2">
                                        <a id="download_image" download=""
                                            class="flex-1 text-center px-3 py-2 bg-slate-100 text-sm rounded-md hover:bg-slate-200">Download</a>
                                    </div>
                                </div>
                            </div>

                            <!-- <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-slate-100">
                                <div class="p-3">
                                    <h3 class="text-sm font-medium text-slate-800 mb-2">Attachment (PDF)</h3>
                                    <div
                                        class="h-48 bg-slate-50 border rounded border-dashed border-slate-200 flex items-center justify-center">
                                        <object data="/path/to/attachment.pdf" type="application/pdf"
                                            class="w-full h-full">
                                            <div class="p-4 text-center">
                                                <iframe id="action_attachment" src="" width="100%"
                                                    height="600px"></iframe>
                                                <p class="text-sm text-slate-600">PDF preview not available.</p>
                                            </div>
                                        </object>
                                    </div>
                                    <div class="p-3 border-t border-slate-100">
                                        <div class="mt-3 flex gap-2">
                                            <a id="download_attachment" download=""
                                                class="flex-1 text-center px-3 py-2 bg-slate-100 text-sm rounded-md hover:bg-slate-200">Download</a>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                        </div>

                        <!-- Right: details -->
                        <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-slate-100">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-1">
                                    <h2 class="text-lg font-semibold text-slate-800">Detailed Description</h2>
                                    <p id="description" class="mt-3 text-slate-600 leading-relaxed">
                                        This is the full description of the action. It can be multiple paragraphs long
                                        and contains all the context, instructions and important notes related to the
                                        action. Use this area to explain the purpose, scope and any dependencies.
                                    </p>

                                    <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Assigned to</dt>
                                            <dd class="mt-1 flex items-center gap-3">
                                                <span id="assigned_avatar"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-600">A</span>
                                                <div>
                                                    <div id="assigned_name" class="text-sm font-medium text-slate-800">
                                                    </div>
                                                </div>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Created by</dt>
                                            <dd class="mt-1 flex items-center gap-3">
                                                <div id="created_by" class="text-sm font-medium text-slate-800">
                                                </div>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Due date</dt>
                                            <dd id="expiry_date" class="mt-1 text-sm text-slate-700">2025-12-31 • <span
                                                    class="text-xs text-green-700 font-semibold">in 30 days</span></dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Location</dt>
                                            <dd id="location" class="mt-1 text-sm text-slate-700">2025-12-31 • <span
                                                    class="text-xs text-green-700 font-semibold">in 30 days</span></dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Related to CCM
                                                topics</dt>
                                            <dd id="related_topics" class="mt-1 text-sm text-slate-700">2025-12-31 •
                                                <span class="text-xs text-green-700 font-semibold">in 30 days</span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Incident Classification
                                            </dt>
                                            <dd id="incident_classfication" class="mt-1 text-sm text-slate-700">
                                                2025-12-31 •
                                                <span class="text-xs text-green-700 font-semibold">in 30 days</span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Site Visit
                                                Duration</dt>
                                            <dd id="visit_duration" class="mt-1 text-sm text-slate-700">2025-12-31 •
                                                <span class="text-xs text-green-700 font-semibold">in 30 days</span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Area Visited
                                                /Department</dt>
                                            <dd id="area_visited" class="mt-1 text-sm text-slate-700">2025-12-31 •
                                                <span class="text-xs text-green-700 font-semibold">in 30 days</span>
                                            </dd>
                                        </div>

                                    </dl>
                                </div>

                                <!-- Status change panel -->
                                <aside class="w-full lg:w-64 bg-slate-50 p-4 rounded-md border border-slate-100">
                                    <h3 class="text-sm font-medium text-slate-800">Status</h3>

                                    <div class="mt-4">
                                        <div id="status" class="flex items-center gap-3">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                            <span class="text-sm text-slate-500">created at</span>
                                        </div>

                                        <label class="block text-xs text-slate-600 mt-4">Change status</label>
                                        <div class="mt-2 flex gap-2">

                                            <button id="mark_closed"
                                                class="px-3 py-2 bg-slate-800 text-white rounded-md text-sm">Mark as
                                                closed</button>
                                        </div>

                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
            const user_id = "<?= $_COOKIE['user_id'] ?? '' ?>";
            const params = new URLSearchParams(window.location.search);
            const actionId = params.get("id");

            if (!actionId) {
                Swal.fire("Error", "No Action ID provided in URL", "error");
                return;
            }

            try {
                // ✅ جلب تفاصيل الإجراء
                const response = await fetch(`../api/actions.php?action=getActionById&id=${actionId}`, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Content-Type": "application/json"
                    }
                });

                const result = await response.json();
                if (!result.success || !result.data) {
                    Swal.fire("Error", "Failed to load action details", "error");
                    return;
                }

                const action = result.data;

                // ✅ تعبئة البيانات داخل الصفحة
                document.getElementById("category").textContent = action.category_name;
                document.getElementById("type").textContent = action.type_name;
                document.getElementById("description").textContent = action.description;
                document.getElementById("location").textContent = action.location;
                document.getElementById("related_topics").textContent = action.related_topics;
                document.getElementById("incident_classfication").textContent = action.incident_classfication;
                document.getElementById("visit_duration").textContent = action.visit_duration;
                document.getElementById("area_visited").textContent = action.area_visited;

                // ✅ الحالة مع فحص تاريخ الانتهاء
                const statusBadge = document.getElementById("status");
                let displayStatus = action.status;

                // تحقق من التاريخ فقط إذا كانت الحالة "open"
                if (action.status === "open" && new Date(action.expiry_date) < new Date()) {
                    displayStatus = "overdue";
                }

                statusBadge.textContent = displayStatus.charAt(0).toUpperCase() + displayStatus.slice(1);

                statusBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${displayStatus === "open"
                    ? "bg-green-100 text-green-800"
                    : displayStatus === "closed"
                        ? "bg-gray-200 text-gray-800"
                        : displayStatus === "overdue"
                            ? "bg-red-100 text-red-800"
                            : "bg-yellow-100 text-yellow-800"
                    }`;


                // التاريخ
                document.getElementById("expiry_date").textContent = action.expiry_date;

                // المستخدم المكلف
                document.getElementById("created_by").textContent = action.created_by_name || "Unassigned";
                document.getElementById("assigned_name").textContent = action.assigned_user_name || "Unassigned";
                document.getElementById("assigned_avatar").textContent =
                    action.assigned_user_name ? action.assigned_user_name.charAt(0).toUpperCase() : "U";

                // الصورة
                const imgElement = document.getElementById("action_image");
                if (action.image) {
                    imgElement.src = `../${action.image}`;
                } else {
                    imgElement.src = "../public/images/logo.png";
                    imgElement.alt = "Default image";
                }

                const downloadImage = document.getElementById("download_image");
                if (action.image) {
                    downloadImage.href = `../${action.image}`;
                    downloadImage.download = action.image.split('/').pop();
                }

                // المرفق PDF
                // const pdfObject = document.getElementById("action_attachment");
                // if (action.attachment) pdfObject.src = `../${action.attachment}`;

                // const downloadAttachment = document.getElementById("download_attachment");
                // if (action.attachment) {
                //     downloadAttachment.href = `../${action.attachment}`;
                //     downloadAttachment.download = action.attachment.split('/').pop();
                // }

                // ✅ التعامل مع زر تغيير الحالة
                const closeBtn = document.getElementById("mark_closed");

                if (closeBtn) {
                    let isOverdue = false;

                    // تحقق من تجاوز التاريخ إذا كانت الحالة "open"
                    if (action.status === "open" && new Date(action.expiry_date) < new Date()) {
                        isOverdue = true;
                    }

                    // السماح بالتغيير فقط إذا:
                    // المستخدم هو المكلّف و الحالة ليست "closed" أو "overdue"
                    if (user_id == action.assigned_user_id && action.status !== "closed" && !isOverdue) {
                        closeBtn.disabled = false;
                        closeBtn.classList.remove("opacity-50", "cursor-not-allowed");
                    } else {
                        closeBtn.disabled = true;
                        closeBtn.classList.add("opacity-50", "cursor-not-allowed");
                    }

                    closeBtn.addEventListener("click", async () => {
                        const confirm = await Swal.fire({
                            title: "Are you sure?",
                            // note input: 'textarea',
                            input: 'textarea',
                            inputLabel: 'Note (optional)',
                            inputPlaceholder: 'Enter a note...',
                            text: "This action will be marked as closed.",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Yes, close it",
                        });

                        if (!confirm.isConfirmed) return;

                        const note = confirm.value || "";

                        try {
                            const updateResponse = await fetch(`../api/actions.php?action=update_status&id=${actionId}`, {
                                method: "PUT",
                                headers: {
                                    "Authorization": `Bearer ${TOKEN}`,
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({ status: "closed", note: note })
                            });

                            const updateResult = await updateResponse.json();

                            if (updateResult.success) {
                                Swal.fire("Updated!", "Action marked as closed.", "success");
                                statusBadge.textContent = "Closed";
                                statusBadge.className =
                                    "inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-800";
                                closeBtn.disabled = true;
                                closeBtn.classList.add("opacity-50", "cursor-not-allowed");
                            } else {
                                Swal.fire("Error", updateResult.message || "Failed to update status.", "error");
                            }
                        } catch (err) {
                            console.error("Error updating status:", err);
                            Swal.fire("Error", "Unable to update status.", "error");
                        }
                    });
                }

            } catch (error) {
                console.error("Error fetching action:", error);
                Swal.fire("Error", "Unable to fetch action data", "error");
            }
        });
    </script>



</body>

</html>