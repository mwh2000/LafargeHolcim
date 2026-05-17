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
    <title>KCML / SLV | Good Practice</title>
</head>

<body>

    <?php renderNavbar('Good Practice'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar(''); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="max-w-6xl mx-auto">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-semibold text-slate-800">Good Practice Details</h1>
                        </div>
                    </div>

                    <!-- Grid: left media, right details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left: media previews -->
                        <div class="md:col-span-1 space-y-4">
                            <h3 class="text-sm font-medium text-slate-800 mb-2">Images</h3>

                            <!-- Container Grid للصور -->
                            <div id="image_container"
                                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-4">
                                <span class="text-xs text-gray-400 col-span-full">No images available</span>
                            </div>
                        </div>

                        <!-- Right: details -->
                        <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-slate-100">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-1">
                                    <h2 class="text-lg font-semibold text-slate-800">Description</h2>
                                    <p id="description" class="mt-3 text-slate-600 leading-relaxed">
                                        Loading description...
                                    </p>

                                    <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-xs text-green-700 font-semibold">Assigned to</dt>
                                            <dd class="mt-1 flex items-center gap-3">
                                                <span id="assigned_avatar"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-600">?</span>
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
                                            <dt class="text-xs text-green-700 font-semibold">Start Date</dt>
                                            <dd id="start_date" class="mt-1 text-sm text-slate-700"></dd>
                                        </div>

                                    </dl>
                                </div>

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
            const params = new URLSearchParams(window.location.search);
            const id = params.get("id");

            if (!id) {
                Swal.fire("Error", "No Good Practice ID provided in URL", "error");
                return;
            }

            try {
                // ✅ جلب التفاصيل
                const response = await fetch(`../api/requester/good_practice.php?id=${id}`, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Content-Type": "application/json"
                    }
                });

                const result = await response.json();
                if (!result.success || !result.data) {
                    Swal.fire("Error", "Failed to load details", "error");
                    return;
                }

                const data = result.data;

                // ✅ تعبئة البيانات داخل الصفحة
                document.getElementById("description").textContent = data.description || 'No description provided.';
                document.getElementById("start_date").textContent = data.start_date || 'N/A';

                // المستخدم المكلف
                document.getElementById("created_by").textContent = data.created_by_name || "Unassigned";
                document.getElementById("assigned_name").textContent = data.assigned_user_name || "Unassigned";
                document.getElementById("assigned_avatar").textContent =
                    data.assigned_user_name ? data.assigned_user_name.charAt(0).toUpperCase() : "U";

                // الصور
                const imagesContainer = document.getElementById("image_container");
                imagesContainer.innerHTML = "";

                if (Array.isArray(data.images) && data.images.length > 0) {
                    data.images.forEach((imgPath, index) => {
                        const wrapper = document.createElement("div");
                        wrapper.className = "relative w-full h-40 border rounded-md overflow-hidden bg-white shadow-sm flex flex-col";

                        const img = document.createElement("img");
                        img.src = `../${imgPath}`;
                        img.alt = `Image ${index + 1}`;
                        img.className = "object-cover w-full h-32"; 

                        const badge = document.createElement("span");
                        badge.textContent = index + 1;
                        badge.className =
                            "absolute top-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded";

                        const download = document.createElement("a");
                        download.href = `../${imgPath}`;
                        download.download = imgPath.split("/").pop();
                        download.textContent = "Download";
                        download.className =
                            "text-center text-xs bg-slate-100 hover:bg-slate-200 px-1 py-0.5 mt-1";

                        wrapper.appendChild(img);
                        wrapper.appendChild(badge);
                        wrapper.appendChild(download);

                        imagesContainer.appendChild(wrapper);
                    });
                } else {
                    imagesContainer.innerHTML =
                        '<span class="text-xs text-gray-400 col-span-full">No images available</span>';
                }

            } catch (error) {
                console.error("Error fetching data:", error);
                Swal.fire("Error", "Unable to fetch data", "error");
            }
        });
    </script>

</body>

</html>
