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
                                <option value="Property">Property</option>
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
                        <div class="col-span-1 sm:col-span-2 lg:col-span-3">
                            <label for="image" class="text-sm text-green-700 mb-2 block">Image (optional)</label>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <label
                                    class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 border border-dashed border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 7v4a1 1 0 001 1h3l2 3 3-4 4 5h3a1 1 0 001-1V7a4 4 0 00-4-4H7a4 4 0 00-4 4z">
                                        </path>
                                    </svg>
                                    <span class="text-sm text-gray-600">Upload Image (jpg, png)</span>
                                    <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png"
                                        class="hidden" />
                                </label>

                                <div id="imagePreview"
                                    class="w-full sm:w-64 h-36 bg-gray-50 border border-gray-200 rounded-md flex items-center justify-center overflow-hidden">
                                    <span class="text-xs text-gray-400">No image selected</span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Preferred size: up to 2MB. JPG, PNG supported.</p>
                        </div>

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

                        const params = new URLSearchParams(window.location.search);
                        const ACTION_ID = params.get("id");
                        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

                        if (!ACTION_ID) {
                            Swal.fire("Error", "Action ID not found in URL", "error");
                        }

                        const API_GET = "../../api/actions.php?action=getActionById&id=" + ACTION_ID;
                        const API_UPDATE = "../../api/requester/actions.php?action=update&id=" + ACTION_ID;
                        const API_TYPES = "../../api/requester/types.php";
                        const API_USERS = "../../api/requester/users.php?action=all";

                        let ORIGINAL_ASSIGNED_USER_ID = null;

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

                        let userSelect = null;

                        /* =========================
                         * 🔹 Image preview
                         * ========================= */
                        el.imageInput?.addEventListener("change", (e) => {
                            const file = e.target.files?.[0];
                            el.imagePreview.innerHTML = "";
                            if (!file) {
                                el.imagePreview.innerHTML = `<span class="text-xs text-gray-400">No image selected</span>`;
                                return;
                            }
                            const img = document.createElement("img");
                            img.src = URL.createObjectURL(file);
                            img.className = "object-cover w-full h-full";
                            el.imagePreview.appendChild(img);
                        });

                        /* =========================
                         * 🔹 Load Categories & Types
                         * ========================= */
                        async function loadCategoriesAndTypes() {
                            const res = await fetch(API_TYPES, {
                                headers: { Authorization: `Bearer ${TOKEN}` }
                            });
                            const { success, data } = await res.json();
                            if (!success) throw new Error("Failed to load types");

                            el.type.innerHTML = `<option value="">Select type</option>`;

                            data.categories.forEach(cat => {
                                const og = document.createElement("optgroup");
                                og.label = cat.name;
                                cat.types.forEach(t => og.appendChild(new Option(t.name, t.id)));
                                el.type.appendChild(og);
                            });
                        }

                        /* =========================
                         * 🔹 Load Users
                         * ========================= */
                        async function loadUsers() {
                            const res = await fetch(API_USERS, {
                                headers: { Authorization: `Bearer ${TOKEN}` }
                            });
                            const data = await res.json();
                            if (!data.success) throw new Error("Failed to load users");

                            el.assignedUser.innerHTML = `<option value="">Select user</option>`;
                            data.data.users.forEach(u => {
                                el.assignedUser.appendChild(
                                    new Option(`${u.name} (${u.email})`, u.id)
                                );
                            });

                            if (userSelect && ORIGINAL_ASSIGNED_USER_ID) {
                                userSelect.setValue(ORIGINAL_ASSIGNED_USER_ID);
                            }


                            userSelect = new TomSelect("#assigned_user", {
                                placeholder: "Search user...",
                                allowEmptyOption: true,
                            });
                        }

                        /* =========================
                         * 🔹 Helper: toDateInput
                         * ========================= */
                        function toDateInput(value) {
                            if (!value) return "";

                            // إذا كان أصلاً YYYY-MM-DD
                            if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                                return value;
                            }

                            const d = new Date(value);
                            if (isNaN(d.getTime())) return "";

                            return d.toISOString().split("T")[0];
                        }


                        /* =========================
                         * 🔹 Load Action Data
                         * ========================= */
                        async function loadActionData() {
                            const res = await fetch(API_GET, {
                                headers: { Authorization: `Bearer ${TOKEN}` }
                            });
                            const { success, data } = await res.json();
                            if (!success) throw new Error("Failed to load action");

                            const a = data;

                            ORIGINAL_ASSIGNED_USER_ID =
                                a.assigned_user_id || a.assigned_user || null;

                            document.getElementById("start_date").value = toDateInput(a.start_date);
                            document.getElementById("visit_duration").value = a.visit_duration ?? "";
                            document.getElementById("area_visited").value = a.area_visited ?? "";
                            document.getElementById("environment").value = a.environment ?? "";
                            document.getElementById("related_topics").value = a.related_topics ?? "";
                            document.getElementById("location").value = a.location ?? "";
                            document.getElementById("incident").value = a.incident ?? "";
                            document.getElementById("description").value = a.description ?? "";
                            document.getElementById("action").value = a.action ?? "";
                            document.getElementById("priority").value = a.priority ?? "";
                            document.getElementById("expiry_date").value = toDateInput(a.expiry_date);

                            if (el.incidentClassification) {
                                el.incidentClassification.value = a.incident_classfication ?? "";
                            }

                            // ✅ type
                            el.type.value = a.type_id;

                            // ✅ assigned user (TomSelect)
                            userSelect.setValue(a.assigned_user_id);

                            // ✅ image
                            if (a.image_url) {
                                el.imagePreview.innerHTML =
                                    `<img src="${a.image_url}" class="object-cover w-full h-full">`;
                            }
                        }

                        /* =========================
                         * 🔹 Submit Update
                         * ========================= */
                        el.form.addEventListener("submit", async (e) => {
                            e.preventDefault();

                            el.submitBtn.disabled = true;
                            el.submitBtn.textContent = "Updating...";

                            const formData = new FormData(el.form);

                            formData.set("assigned_user_id", el.assignedUser.value || ORIGINAL_ASSIGNED_USER_ID);
                            formData.set("created_by", "<?= $_COOKIE['user_id'] ?? '' ?>");
                            formData.set("id", ACTION_ID); // عشان ما يصير Missing ID

                            // حوّل FormData إلى URLSearchParams
                            const urlEncodedData = new URLSearchParams(formData).toString();

                            try {
                                const res = await fetch(API_UPDATE, {
                                    method: "PUT",
                                    headers: {
                                        Authorization: `Bearer ${TOKEN}`,
                                        "Content-Type": "application/x-www-form-urlencoded"
                                    },
                                    body: urlEncodedData
                                });

                                const data = await res.json();
                                if (!data.success) throw new Error(data.message);

                                Swal.fire("Success", "Action updated successfully", "success");

                            } catch (err) {
                                Toastify({
                                    text: err.message,
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#ff5f6d",
                                }).showToast();
                            } finally {
                                el.submitBtn.disabled = false;
                                el.submitBtn.textContent = "Update";
                            }
                        });


                        /* =========================
                         * 🔹 Init (ORDER MATTERS)
                         * ========================= */
                        document.addEventListener("DOMContentLoaded", async () => {
                            try {
                                await loadCategoriesAndTypes();
                                await loadUsers();
                                await loadActionData();
                            } catch (e) {
                                Swal.fire("Error", e.message, "error");
                            }
                        });
                    </script>


                    <script>
                        // Image preview and attachment name display (keeps existing ids)
                        (function () {
                            const imgInput = document.getElementById('image');
                            const imgPreview = document.getElementById('imagePreview');
                            // const attachmentInput = document.getElementById('attachment');
                            // const attachmentName = document.getElementById('attachmentName');

                            imgInput?.addEventListener('change', (e) => {
                                const file = e.target.files && e.target.files[0];
                                imgPreview.innerHTML = '';
                                if (!file) {
                                    imgPreview.innerHTML = '<span class="text-xs text-gray-400">No image selected</span>';
                                    return;
                                }
                                const url = URL.createObjectURL(file);
                                const img = document.createElement('img');
                                img.src = url;
                                img.alt = file.name || 'Preview';
                                img.className = 'object-cover w-full h-full';
                                imgPreview.appendChild(img);
                            });

                            // attachmentInput?.addEventListener('change', (e) => {
                            //     const file = e.target.files && e.target.files[0];
                            //     attachmentName.textContent = file ? file.name : '';
                            // });
                        })();
                    </script>
                </div>
            </main>
        </div>
    </div>

</body>

</html>