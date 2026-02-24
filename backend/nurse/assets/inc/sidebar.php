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

/* 3. FORCE VISIBILITY */
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
                <li class="menu-title">Main Navigation</li>

                <li>
                    <a href="his_doc_dashboard.php" class="hover:bg-white hover:rounded-xl flex items-center font-sans">
                        <i class="fe-airplay text-2xl text-blue-600"></i>
                        <span class="font-medium text-black ml-2"> Dashboard </span>
                    </a>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="fab fa-accessible-icon text-2xl text-green-500"></i>
                        <span class="font-medium text-black ml-2"> Patients </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li>
                            <a href="his_doc_view_patients.php" class="text-black">View Patients</a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="fas fa-calendar-check text-2xl text-yellow-500"></i>
                        <span class="font-medium text-black ml-2"> Appointment </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level relative" aria-expanded="false">
                        <li>
                            <a href="his_admin_view_appointments.php" class="text-black">View Appointment</a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
                        <i class="mdi mdi-cash-refund text-2xl text-lime-500"></i>
                        <span class="font-medium text-black ml-2"> Payrolls </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li>
                            <a href="his_doc_view_payrolls.php" class="text-black">My Payrolls</a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>