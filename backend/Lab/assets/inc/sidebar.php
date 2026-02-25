<style>
/* Reset & Base Structure */
.metismenu li {
    position: relative;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Sub-menu Container */
.nav-second-level {
    padding-left: 45px;
    list-style: none;
    background-color: transparent;
    margin-bottom: 2px;
    overflow: hidden;
}

/* Force Visibility for Active/Hover states */
.metismenu li.mm-active > .nav-second-level,
.nav-second-level.mm-show,
.metismenu li:hover > .nav-second-level {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    position: relative !important;
}

/* Sub-link Styling */
.nav-second-level li a {
    padding: 8px 0;
    display: block !important;
    color: #333333 !important;
    font-size: 14px;
    transition: all 0.3s ease;
    text-decoration: none;
    opacity: 0.8;
}

/* Hover effect for sub-links */
.nav-second-level li a:hover {
    color: #007bff !important; 
    opacity: 1 !important;
    padding-left: 8px; 
}

/* Separator Line inside sub-menus */
.nav-second-level hr {
    margin: 2px 0;
   
    border-top: 1px solid #ebeef2;
  
}
</style>

<div class="left-side-menu">
    <div class="slimscroll-menu">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">
                <li class="menu-title text-uppercase font-weight-bold" style="letter-spacing: 0.05em; font-size: 11px; color: #98a6ad;">Navigation</li>

                <li>
                    <a href="his_doc_dashboard.php" class="hover:bg-light flex items-center py-3 transition-all rounded-lg my-1">
                        <i class="fe-airplay text-2xl text-blue-600"></i>
                        <span class="font-medium text-black ml-3"> Dashboard </span>
                    </a>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center py-3  transition-all rounded-lg  my-1">
                        <i class="mdi mdi-flask text-2xl text-purple-500"></i>
                        <span class="font-medium text-black ml-3"> Laboratory </span>
                        <span class="menu-arrow ml-auto"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li>
                            <a href="his_doc_view_patients.php">Patient Lab Results</a>
                        </li>
                        <li>
                            <a href="his_doc_lab_report.php">Lab Reports</a>
                        </li>
                        <hr>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center py-3  transition-all rounded-lg  my-1">
                        <i class="fas fa-funnel-dollar text-2xl text-orange-500"></i>
                        <span class="font-medium text-black ml-3"> Inventory </span>
                        <span class="menu-arrow ml-auto"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li>
                            <a href="his_doc_pharm_inventory.php">Pharmaceuticals</a>
                        </li>
                        <li>
                            <a href="his_doc_equipments_inventory.php">Assets</a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>