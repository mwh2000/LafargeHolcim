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
    <title>KCML / SLV | New Report</title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('New Report', '/public/notifications.php'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('create_action'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">New Report</h1>
                </div>

                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <form id="createActionForm" enctype="multipart/form-data"
                        class="max-w-5xl mx-auto grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 p-4 sm:p-6">

                        <!-- Group -->
                        <div class="col-span-1">
                            <label for="group" class="text-sm text-green-700 mb-2 block">Group</label>
                            <select id="group" name="group"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value="">Select Group</option>
                                <!-- options from A to M capital -->
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="F">F</option>
                                <option value="G">G</option>
                                <option value="H">H</option>
                                <option value="I">I</option>
                                <option value="J">J</option>
                                <option value="K">K</option>
                                <option value="L">L</option>
                                <option value="M">M</option>
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="col-span-1">
                            <label for="start_date" class="text-sm text-green-700 mb-2 block">Start Date</label>
                            <input id="start_date" name="start_date" type="date"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md focus:ring-1 focus:ring-[#0b6f76] focus:outline-none" />
                        </div>
                        <!-- Type (full width) -->
                        <div class="col-span-1 sm:col-span-2 lg:col-span-3">
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
                                <option value="">Select Environment</option>
                                <option value="H.k">H.k</option>
                                <option value="Weater">Weater</option>
                                <option value="Dustiment">Dustiment</option>
                            </select>
                        </div>
                        <!-- Area Visited /Department -->
                        <div class="col-span-1">
                            <label for="area_visited" class="text-sm text-green-700 mb-2 block">Area Visited
                                /Department</label>
                            <select id="area_visited" name="area_visited"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value="">Select Area Visited
                                    /Department</option>
                                <option value="prodution">Prodution</option>
                                <option value="mechanic">Mechanic</option>
                                <option value="safety">Safety</option>
                                <option value="security">Security</option>
                                <option value="adminstration">Adminstration</option>
                                <option value="quarry">Quarry</option>
                                <option value="packing">Packing</option>
                                <option value="quality">Quality</option>
                                <option value="HFO Area">HFO Area</option>
                                <option value="despatch">Despatch</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Site Visit Duration -->
                        <div class="col-span-1">
                            <label for="visit_duration" class="text-sm text-green-700 mb-2 block">Site Visit
                                Duration</label>
                            <select id="visit_duration" name="visit_duration"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value="">Select Site Visit Duration</option>
                                <option value="10">10</option>
                                <option value="30">30</option>
                                <option value="60">60</option>
                                <option value="120">120</option>
                            </select>
                        </div>

                        <!-- Related to CCM topics -->
                        <div class="col-span-1">
                            <label for="related_topics" class="text-sm text-green-700 mb-2 block">Related to CCM
                                topics</label>
                            <select id="related_topics" name="related_topics"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value="">Select Related to CCM topics</option>
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
                                <option value="">Select Location</option>
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

                        <!-- Did the Incident Cause one of the following -->
                        <div class="col-span-1">
                            <label for="incident_cause" class="text-sm text-green-700 mb-2 block">Incident</label>
                            <select id="incident_cause" name="incident_cause"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value="">Incident</option>
                                <option value="FA (First aid)">FA (First aid)</option>
                                <option value="MI (Medical Injury)">MI (Medical Injury)</option>
                                <option value="LTI (Lost Time Injury)">LTI (Lost Time Injury)</option>
                                <option value="PD (Property Damage)">PD (Property Damage)</option>
                                <option value="none">None</option>
                            </select>
                        </div>

                        <!-- Assigned User -->
                        <div class="col-span-1">
                            <label for="assigned_user" class="text-sm text-green-700 mb-2 block">Assigned User</label>
                            <select id="assigned_user" name="assigned_user"
                                class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                                <option value="">Select User</option>
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

                        <!-- Expiry Date -->
                        <div class="col-span-1">
                            <label for="expiry_date" class="text-sm text-green-700 mb-2 block">Expiry Date</label>
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
                            <button type="submit"
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

    <script>
        const API_USERS = "../../api/requester/users.php?action=all";
        const API_TYPES = "../../api/requester/types.php";
        const API_CREATE = "../../api/requester/actions.php?action=create";
        const ID = "<?= $_SESSION['id'] ?? '' ?>";
        const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";

        /**
         * 🔹 تحميل المستخدمين
         */
        async function loadUsers() {
            try {
                const res = await fetch(API_USERS, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const data = await res.json();
                const select = document.getElementById("assigned_user");

                if (!data.success) throw new Error(data.message);
                (data.data?.users || []).forEach(user => {
                    const opt = document.createElement("option");
                    opt.value = user.id;
                    opt.textContent = `${user.name} (${user.email})`;
                    select.appendChild(opt);
                });
            } catch (e) {
                console.error(e);
            }
        }

        /**
         * 🔹 تحميل الكاتيجوريز والأنواع
         */
        async function loadCategoriesAndTypes() {
            try {
                const res = await fetch(API_TYPES, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const data = await res.json();
                console.log("📦 Types Data:", data); // لتأكيد البيانات في الكونسول

                // ✅ البيانات موجودة داخل data.data.categories
                const categories = data.data?.categories || [];

                if (!data.success || !Array.isArray(categories)) {
                    throw new Error(data.message || "Invalid response");
                }

                const typeSelect = document.getElementById("type");
                if (!typeSelect) {
                    console.error("❌ Element with id='type' not found in DOM!");
                    return;
                }

                // تنظيف القائمة
                typeSelect.innerHTML = "";

                // تعبئة الأنواع والفئات
                categories.forEach(category => {
                    const optGroup = document.createElement("optgroup");
                    optGroup.label = category.name;

                    if (Array.isArray(category.types) && category.types.length > 0) {
                        category.types.forEach(type => {
                            const option = document.createElement("option");
                            option.value = type.id;
                            option.textContent = type.name;
                            optGroup.appendChild(option);
                        });
                    } else {
                        const emptyOption = document.createElement("option");
                        emptyOption.disabled = true;
                        emptyOption.textContent = "(No types)";
                        optGroup.appendChild(emptyOption);
                    }

                    typeSelect.appendChild(optGroup);
                });

            } catch (e) {
                console.error("Error loading categories/types:", e);
            }
        }


        /**
         * 🔹 إنشاء الأكشن
         */
        document.getElementById("createActionForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append("type_id", document.getElementById("type").value);
            formData.append("group", document.getElementById("group").value);
            formData.append("location", document.getElementById("location").value);
            formData.append("related_topics", document.getElementById("related_topics").value);
            formData.append("incident_cause", document.getElementById("incident_cause").value);
            formData.append("visit_duration", document.getElementById("visit_duration").value);
            formData.append("environment", document.getElementById("environment").value);
            formData.append("area_visited", document.getElementById("area_visited").value);
            formData.append("description", document.getElementById("description").value);
            formData.append("action", document.getElementById("action").value);
            formData.append("assigned_user_id", document.getElementById("assigned_user").value);
            formData.append("start_date", document.getElementById("start_date").value);
            formData.append("expiry_date", document.getElementById("expiry_date").value);
            formData.append("image", document.getElementById("image").files[0] || "");
            // formData.append("attachment", document.getElementById("attachment").files[0] || "");
            formData.append("created_by", ID);

            try {
                const res = await fetch(API_CREATE, {
                    method: "POST",
                    headers: { "Authorization": `Bearer ${TOKEN}` },
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

                e.target.reset();
            } catch (err) {
                Toastify({
                    text: err.message || "Something went wrong.",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#ff5f6d",
                    stopOnFocus: true,
                }).showToast();
            }
        });

        // ✅ تحميل البيانات عند فتح الصفحة
        document.addEventListener("DOMContentLoaded", () => {
            loadUsers();
            loadCategoriesAndTypes();
        });
    </script>
</body>

</html>