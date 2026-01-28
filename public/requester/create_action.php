<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';

require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';
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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script defer type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script src="/action.js"></script>

    <title>KCML / SLV | New Report</title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('New Report'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('create_action'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">New Report
                        <?php echo $userData['group'] ? '- group ' . $userData['group'] : ''; ?>
                    </h1>
                </div>

                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <form id="createActionForm" enctype="multipart/form-data"
                        class="max-w-5xl mx-auto grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 p-4 sm:p-6">

                        <!-- Group -->


                        <!-- Start Date -->
                        <div class="col-span-1">
                            <label for="start_date" class="text-sm text-green-700 mb-2 block">Start Date</label>
                            <input id="start_date" name="start_date" type="date"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md focus:ring-1 focus:ring-[#0b6f76] focus:outline-none" />
                        </div>

                        <!-- Site Visit Duration -->
                        <div class="col-span-1">
                            <label for="visit_duration" class="text-sm text-green-700 mb-2 block">Visit Duration</label>
                            <select id="visit_duration" name="visit_duration"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="10">10</option>
                                <option value="30">30</option>
                                <option value="60">60</option>
                                <option value="120">120</option>
                            </select>
                        </div>

                        <!-- Area Visited /Department -->
                        <div class="col-span-1">
                            <label for="area_visited" class="text-sm text-green-700 mb-2 block">Area Visited
                                /Department</label>
                            <select id="area_visited" name="area_visited"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="quarry">Quarry</option>
                                <option value="packing">Packing</option>
                                <option value="quality">Quality</option>
                                <option value="HFO Area">HFO Area</option>
                                <option value="despatch">Despatch</option>
                                <option value="Quarry">Quarry</option>
                                <option value="Cement Mill">Cement Mill</option>
                                <option value="Crusher">Crusher</option>
                                <option value="Clinker">Clinker</option>
                                <option value="Packing">Packing</option>
                                <option value="Despatch">Despatch</option>
                            </select>
                        </div>
                        <!-- Type (full width) -->
                        <div class="col-span-1">
                            <label for="type" class="text-sm text-green-700 mb-2 block">Safety</label>
                            <select id="type" aria-label="Type"
                                class="w-full block px-4 py-3 border border-gray-200 rounded-md bg-white text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-[#0b6f76]">
                                <option value="">Select Type</option>
                            </select>
                        </div>

                        <!-- Environment -->
                        <div class="col-span-1">
                            <label for="environment" class="text-sm text-green-700 mb-2 block">Environment</label>
                            <select id="environment" name="environment"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="HK">HK</option>
                                <option value="Water Pollution">Water Pollution</option>
                                <option value="Dust emissions">Dust emissions</option>
                                <option value="NCR">NCR</option>
                            </select>
                        </div>

                        <!-- Related to CCM topics -->
                        <div class="col-span-1">
                            <label for="related_topics" class="text-sm text-green-700 mb-2 block">Related to CCM
                                topics</label>
                            <select id="related_topics" name="related_topics"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="Fatal Road Crush">Fatal Road Crush</option>
                                <option value="Fall from height">Fall from height</option>
                                <option value="Contact With Hot Meal">Contact With Hot Meal</option>
                                <option value="Respiratory">Respiratory</option>
                                <option value="Liquid Fuel Fire">Liquid Fuel Fire</option>
                                <option value="Material Engulfment">Material Engulfment</option>
                                <option value="Structure Collaps">Structure Collaps</option>
                                <option value="ERP">ERP</option>
                                <option value="Mobile equipment incidents">Mobile equipment incidents</option>
                                <option value="Water Discharge">Water Discharge</option>
                                <option value="Contact with Hazardous energy">Contact with Hazardous energy</option>
                                <option value="none">None</option>
                            </select>
                        </div>

                        <!-- Location -->
                        <div class="col-span-1">
                            <label for="location" class="text-sm text-green-700 mb-2 block">Location</label>
                            <select id="location" name="location"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="Kiln & Cooler">Kiln & Cooler</option>
                                <option value="Pre-heater">Pre-heater</option>
                                <option value="Boiler & HFO Area">Boiler & HFO Area</option>
                                <option value="RM">RM</option>
                                <option value="Crusher">Crusher</option>
                                <option value="CM">CM</option>
                                <option value="Quarry">Quarry</option>
                                <option value="HFO tanks">HFO tanks</option>
                                <option value="Packing plant">Packing plant</option>
                                <option value="Dispatch">Dispatch</option>
                                <option value="PT buses parking area">PT buses parking area</option>
                                <option value="Compressors building">Compressors building</option>
                                <option value="Cresta silos">Cresta silos</option>
                                <option value="others">Others</option>
                            </select>
                        </div>

                        <!-- Incident Classification -->
                        <?php if (($_COOKIE['user_type'] ?? null) == 4): ?>
                            <div class="col-span-1">
                                <label for="incident_classfication" class="text-sm text-green-700 mb-2 block">
                                    Incident Classification
                                </label>
                                <select id="incident_classfication" name="incident_classfication"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                    <option value=""></option>
                                    <option value="FA (First aid)">FA (First aid)</option>
                                    <option value="MI (Medical Injury)">MI (Medical Injury)</option>
                                    <option value="LTI (Lost Time Injury)">LTI (Lost Time Injury)</option>
                                    <option value="PD (Property Damage)">PD (Property Damage)</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Incident -->
                        <div class="col-span-1">
                            <label for="incident" class="text-sm text-green-700 mb-2 block">Incident</label>
                            <select id="incident" name="incident"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="Injuries">Injuries</option>
                                <option value="Property Damage">Property Damage</option>
                                <option value="Fire">Fire</option>
                            </select>
                        </div>

                        <!-- Assigned User -->
                        <div class="col-span-1">
                            <label for="assigned_user" class="text-sm text-green-700 mb-2 block">Assigned User</label>
                            <select id="assigned_user" name="assigned_user"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                            </select>
                        </div>

                        <!-- Description (full width) -->
                        <div class="col-span-1 sm:col-span-2 lg:col-span-3">
                            <label for="description" class="text-sm text-green-700 mb-2 block">Description</label>
                            <textarea id="description" name="description" rows="4"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md focus:ring-1 focus:ring-[#0b6f76] focus:outline-none"></textarea>
                        </div>

                        <!-- Action -->
                        <div class="col-span-1 sm:col-span-2 lg:col-span-3">
                            <label for="action" class="text-sm text-green-700 mb-2 block">Action</label>
                            <input id="action" name="action"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md focus:ring-1 focus:ring-[#0b6f76] focus:outline-none"></input>
                        </div>

                        <!-- Priority -->
                        <div class="col-span-1">
                            <label for="priority" class="text-sm text-green-700 mb-2 block">Priority</label>
                            <select id="priority" name="priority"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value=""></option>
                                <option value="H">H</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="Major">Major</option>
                            </select>
                        </div>

                        <!-- Due Date -->
                        <div class="col-span-1">
                            <label for="expiry_date" class="text-sm text-green-700 mb-2 block">Due date</label>
                            <input id="expiry_date" name="expiry_date" type="date"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md focus:ring-1 focus:ring-[#0b6f76] focus:outline-none" />
                        </div>

                        <!-- Attachment (PDF) -->
                        <!-- <div class="col-span-1">
                            <label for="attachment" class="text-sm text-green-700 mb-2 block">Attachment (PDF)</label>
                            <label
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 border border-dashed border-gray-200 rounded-md hover:bg-gray-50">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span class="text-sm text-gray-600">Choose a PDF file (optional)</span>
                                <input id="attachment" name="attachment" type="file" accept=".pdf" class="hidden" />
                            </label>
                            <p id="attachmentName" class="mt-2 text-xs text-gray-500 truncate"></p>
                        </div> -->

                        <!-- Image Upload with preview -->
                        <!-- Upload Button -->
                        <div class="col-span-full">
                            <label
                                class="inline-flex items-center px-4 py-2 border border-dashed rounded-md cursor-pointer bg-white hover:bg-gray-50">
                                <span class="text-sm">Upload Images</span>
                                <input
                                    id="images"
                                    name="images[]"
                                    type="file"
                                    accept=".jpg,.jpeg,.png"
                                    multiple
                                    class="hidden">
                            </label>
                        </div>

                        <!-- Preview Row -->
                        <div
                            id="imagePreview"
                            class="col-span-full grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mt-2
                                max-h-64 overflow-y-auto p-2
                                bg-gray-50 border border-gray-200 rounded-md"></div>


                        <!-- Submit -->
                        <div class="col-span-1 sm:col-span-2 lg:col-span-3 mt-2 flex justify-end">
                            <button type="submit" id="submitBtn"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-[#0b6f76] text-white text-sm font-medium rounded-lg hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-[#0b6f76]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Submit
                            </button>
                        </div>

                    </form>


                    <script>
                        const API_USERS = "../../api/requester/users.php?action=all";
                        const API_TYPES = "../../api/requester/types.php";
                        const API_CREATE = "../../api/requester/actions.php?action=create";
                        const ID = "<?= $_COOKIE['user_id'] ?? '' ?>";
                        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

                        const el = {
                            form: document.getElementById("createActionForm"),
                            submitBtn: document.getElementById("submitBtn"),
                            assignedUser: document.getElementById("assigned_user"),
                            incidentClassification: document.getElementById("incident_classfication"),
                            incident: document.getElementById("incident"),
                            cmm: document.getElementById("related_topics"),
                            type: document.getElementById("type"),
                            imageInput: document.getElementById("image"),
                            imagePreview: document.getElementById("imagePreview"),
                        };


                        /* =========================
                         * 🔹 Users & Roles Logic
                         * ========================= */
                        let userSelect;

                        let filter_roles = [];

                        const roleSources = {
                            incidentClassification: [],
                            cmm: [],
                            incident: [],
                            type: []
                        };

                        // دالة لدمج كل القيم وتحديث المستخدمين
                        function updateFilterRoles() {
                            filter_roles = [
                                ...new Set(
                                    Object.values(roleSources).flat()
                                )
                            ];
                            loadUsers();
                        }

                        // دالة عامة للتعامل مع أي input وتعيين قيمه الخاصة
                        function handleRolesChange(sourceKey, roles, value) {
                            roleSources[sourceKey] = value ? roles : [];
                            updateFilterRoles();
                        }

                        // ربط inputs مع handler الخاص بهم
                        [
                            ["incidentClassification", el.incidentClassification, [3, 4]],
                            ["cmm", el.cmm, [3, 4]],
                        ].forEach(([key, element, roles]) => {
                            element?.addEventListener("change", function() {
                                handleRolesChange(key, roles, this.value);
                            });
                        });

                        // input آخر مستقل
                        el.incident?.addEventListener("change", function() {
                            handleRolesChange("incident", [2, 4], this.value);
                        });

                        // دالة للحصول على اسم optgroup لأي خيار محدد
                        function getSelectedOptGroupName(selectEl) {
                            const option = selectEl.selectedOptions[0]; // الخيار المحدد
                            if (!option) return null;

                            const optGroup = option.parentElement;
                            if (optGroup && optGroup.tagName === "OPTGROUP") {
                                return optGroup.label; // اسم الـ optgroup
                            }

                            return null; // إذا الخيار بدون optgroup
                        }

                        // التعامل مع type
                        el.type.addEventListener("change", function() {
                            const groupName = getSelectedOptGroupName(this);
                            if (["NM", "VPC", "Hazard"].includes(groupName)) {
                                handleRolesChange("type", [2, 4], true);
                            } else if (["CVPC"].includes(groupName)) {
                                handleRolesChange("type", [3, 4], true); // إذا تريد مسح القيم للبقية
                            } else {
                                handleRolesChange("type", [], false);
                            }
                        });

                        async function loadUsers() {
                            try {
                                let url = API_USERS;

                                const select = document.getElementById("assigned_user");
                                if (userSelect) {
                                    userSelect.destroy();
                                    userSelect = null;
                                }

                                // نظف القائمة واضف خيار مؤقت
                                select.innerHTML = `<option value="">Loading...</option>`;

                                if (filter_roles.length > 0) {
                                    url += `&role_id=${filter_roles.join(",")}`;
                                }

                                const res = await fetch(url, {
                                    headers: {
                                        "Authorization": `Bearer ${TOKEN}`
                                    }
                                });

                                const data = await res.json();
                                if (!data.success) throw new Error(data.message);

                                const users = data.data?.users || [];

                                select.innerHTML = `<option value="">Select user</option>`;

                                users.forEach(user => {
                                    const opt = document.createElement("option");
                                    opt.value = user.id;
                                    opt.textContent = `${user.name} (${user.email})`;
                                    select.appendChild(opt);
                                });

                                if (userSelect) userSelect.destroy();

                                userSelect = new TomSelect("#assigned_user", {
                                    placeholder: "Search user...",
                                    allowEmptyOption: true,
                                    searchField: ["text"],
                                });

                            } catch (e) {
                                console.error(e);
                            }
                        }
                        /* =========================
                         * 🔹 Categories & Types
                         * ========================= */
                        async function loadCategoriesAndTypes() {
                            try {
                                const res = await fetch(API_TYPES, {
                                    headers: {
                                        Authorization: `Bearer ${TOKEN}`
                                    }
                                });

                                const {
                                    success,
                                    data,
                                    message
                                } = await res.json();
                                if (!success) throw new Error(message);

                                const categories = data?.categories || [];
                                el.type.innerHTML = `<option value="">Select type</option>`;

                                categories.forEach(category => {
                                    const optGroup = document.createElement("optgroup");
                                    optGroup.label = category.name;

                                    (category.types || []).forEach(type => {
                                        optGroup.appendChild(
                                            new Option(type.name, type.id)
                                        );
                                    });

                                    el.type.appendChild(optGroup);
                                });

                            } catch (err) {
                                console.error("❌ Load categories error:", err);
                            }
                        }
                        /* =========================
                         * 🔹 Submit Form
                         * ========================= */
                        el.form.addEventListener("submit", async (e) => {
                            e.preventDefault();

                            el.submitBtn.disabled = true;
                            el.submitBtn.textContent = "Submitting...";

                            const formData = new FormData();
                            formData.append("type_id", document.getElementById("type").value);
                            formData.append("location", document.getElementById("location").value);
                            formData.append("related_topics", document.getElementById("related_topics").value);

                            const incidentClassificationEl =
                                document.getElementById("incident_classfication");

                            if (incidentClassificationEl && incidentClassificationEl.value !== "") {
                                formData.append("incident_classfication", incidentClassificationEl.value);
                            }

                            formData.append("incident", document.getElementById("incident").value);
                            formData.append("visit_duration", document.getElementById("visit_duration").value);
                            formData.append("environment", document.getElementById("environment").value);
                            formData.append("area_visited", document.getElementById("area_visited").value);
                            formData.append("description", document.getElementById("description").value);
                            formData.append("action", document.getElementById("action").value);
                            formData.append("priority", document.getElementById("priority").value);
                            formData.append("assigned_user_id", document.getElementById("assigned_user").value);
                            formData.append("start_date", document.getElementById("start_date").value);
                            formData.append("expiry_date", document.getElementById("expiry_date").value);
                            const imagesInput = document.getElementById("images");
                            [...imagesInput.files].forEach(file => {
                                formData.append("images[]", file);
                            });

                            formData.append("created_by", ID);

                            try {
                                const res = await fetch(API_CREATE, {
                                    method: "POST",
                                    headers: {
                                        Authorization: `Bearer ${TOKEN}`
                                    },
                                    body: formData
                                });

                                const data = await res.json();
                                if (!data.success) throw new Error(data.message);

                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: "Action created successfully!",
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                el.form.reset();
                                el.assignedUser.tomselect.clear();

                            } catch (err) {
                                Toastify({
                                    text: err.message || "Something went wrong.",
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#ff5f6d",
                                }).showToast();
                            } finally {
                                el.submitBtn.disabled = false;
                                el.submitBtn.textContent = "Submit";
                            }
                        });

                        /* =========================
                         * 🔹 Init
                         * ========================= */
                        document.addEventListener("DOMContentLoaded", () => {
                            loadUsers();
                            loadCategoriesAndTypes();
                        });
                    </script>

                    <script>
                        const imagesInput = document.getElementById("images");
                        const imagePreview = document.getElementById("imagePreview");

                        imagesInput?.addEventListener("change", (e) => {
                            imagePreview.innerHTML = "";

                            const files = [...e.target.files];
                            if (files.length === 0) {
                                imagePreview.innerHTML =
                                    '<span class="text-xs text-gray-400 col-span-full">No images selected</span>';
                                return;
                            }

                            files.forEach((file, index) => {
                                const wrapper = document.createElement("div");
                                wrapper.className =
                                    "relative w-full h-32 border rounded overflow-hidden bg-white";

                                const img = document.createElement("img");
                                img.src = URL.createObjectURL(file);
                                img.className = "object-cover w-full h-full";

                                // رقم الصورة (اختياري)
                                const badge = document.createElement("span");
                                badge.textContent = index + 1;
                                badge.className =
                                    "absolute top-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded";

                                wrapper.appendChild(img);
                                wrapper.appendChild(badge);

                                imagePreview.appendChild(wrapper);
                            });
                        });
                    </script>

                </div>
            </main>
        </div>
    </div>

</body>

</html>