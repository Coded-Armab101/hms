<style>
/* 1. Reset & Base Structure */
.metismenu li {
    position: relative;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* 2. The Sub-menu Container */
.nav-second-level {
    padding-left: 45px;
    list-style: none;
    background-color: transparent;
    margin-bottom: 10px;
    overflow: hidden;
}

/* 3. FORCE VISIBILITY Logic */
.metismenu li.mm-active > .nav-second-level,
.nav-second-level.mm-show,
.metismenu li:hover > .nav-second-level {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    position: relative !important;
    top: 0 !important;
    left: 0 !important;
}

/* 4. Sub-link Styling */
.nav-second-level li a {
    padding: 8px 0;
    display: block !important;
    color: #000000 !important;
    font-size: 14.5px;
    transition: all 0.3s ease;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    text-decoration: none;
    opacity: 0.8;
    visibility: visible !important;
}

/* 5. Hover effect for sub-links */
.nav-second-level li a:hover {
    color: #007bff !important; 
    opacity: 1 !important;
    padding-left: 5px; 
}

/* 6. Separator Line */
.nav-second-level hr {
    margin: 8px 0;
    border: 0;
    border-top: 1px solid #e5e7eb;
    width: 80%;
}
</style>

<div class="left-side-menu">
    <div class="slimscroll-menu">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Navigation</li>

                <li>
                    <a href="his_pharm_dashboard.php" class="flex items-center font-sans">
                        <i class="fe-airplay text-2xl text-blue-600"></i>
                        <span class="font-medium text-black ml-2"> Dashboard </span>
                    </a>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="mdi mdi-pill text-2xl text-pink-500"></i>
                        <span class="font-medium text-black ml-2"> Pharmacy </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a href="his_pharm_add_pharm_cat.php">Add Pharm Category</a></li>
                        <li><a href="his_pharm_view_pharm_cat.php">View Pharm Category</a></li>
                        <li><a href="his_pharm_manage_pharm_cat.php">Manage Pharm Category</a></li>
                        <hr>
                        <li><a href="his_pharm_add_pharmaceuticals.php">Add Pharmaceuticals</a></li>
                        <li><a href="his_pharm_view_pharmaceuticals.php">View Pharmaceuticals</a></li>
                        <li><a href="his_pharm_manage_pharmaceuticals.php">Manage Pharmaceuticals</a></li>
                        <hr>
                        <li><a href="his_pharm_add_presc.php">Dispense Drugs</a></li>
                        <li><a href="his_pharm_view_presc.php">View Dispense Drugs</a></li>
                        <li><a href="his_pharm_manage_presc.php">Manage Dispense Drug</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="fas fa-box-open text-2xl text-orange-500"></i>
                        <span class="font-medium text-black ml-2"> Inventory </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a href="his_pharm_pharm_inventory.php">Pharmaceuticals</a></li>
                        <li><a href="his_pharm_equipments_inventory.php">Assets</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="fas fa-truck text-2xl text-purple-500"></i>
                        <span class="font-medium text-black ml-2"> Vendors </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a href="his_pharm_add_vendor.php">Add Vendor</a></li>
                        <li><a href="his_pharm_manage_vendor.php">Manage Vendors</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="fas fa-lock text-2xl text-red-500"></i>
                        <span class="font-medium text-black ml-2"> Password Resets </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a href="his_pharm_manage_password_resets.php">Manage</a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>