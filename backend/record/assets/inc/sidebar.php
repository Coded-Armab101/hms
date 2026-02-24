<style>
    /* This keeps the dropdown open while you are moving the mouse over it */
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
    /* display: none;  */
    /* visibility: hidden; */
    /* opacity: 0; */
}

/* 3. FORCE VISIBILITY (The Fix) */
/* This ensures that once clicked OR hovered, the menu stays visible and in place */
.metismenu li.mm-active > .nav-second-level,
.nav-second-level.mm-show,
.metismenu li:hover > .nav-second-level {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    position: relative !important; /* Prevents floating/overlapping */
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
    opacity: 0.8; /* Slightly faint when not hovered */
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

                    <!--- Sidemenu -->
                    <div id="sidebar-menu">

                        <ul class="metismenu" id="side-menu">

                            <li class="menu-title"> Main Navigation</li>
                  <li>
                     <a href="his_admin_dashboard.php" class="hover:bg-white hover:rounded-xl flex items-center font-sans">
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
            <a href="his_admin_register_patient.php" class="text-black">Register Patient</a>
        </li>
        <li>
            <a href="his_admin_view_patients.php" class="text-black">View Patients</a>
        </li>
        <li>
            <a href="his_admin_manage_patient.php" class="text-black">Manage Patients</a>
        </li>
        <hr class="my-1 border-gray-200">
        <li>
            <a href="his_admin_discharge_patient.php" class="text-black">Discharge Patients</a>
        </li>
        <li>
            <a href="his_admin_patient_transfer.php" class="text-black">Patient Transfers</a>
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
            <a href="his_admin_appointment.php" class="text-black">Book Appointment</a>
        </li>
        <li>
            <a href="his_admin_view_appointments.php" class="text-black">View Appointment</a>
        </li>
    </ul>
</li>
                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="mdi mdi-clipboard-pulse text-2xl text-pink-500"></i>
        <span class="font-medium text-black ml-2"> Medical Screening </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_update_screening_form.php" class="text-black">My Screening Submissions</a>
        </li>
    </ul>
</li>

                            <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="mdi mdi-doctor text-2xl text-red-600"></i>
        <span class="font-medium text-black ml-2"> Employees </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_employee_cat.php">Add Employee</a>
        </li>
        <li>
            <a href="his_admin_view_employee.php">View Employees</a>
        </li>
        <li>
            <a href="his_admin_manage_employee.php">Manage Employees</a>
        </li>
        <hr class="my-1 border-gray-200">
        <li>
            <a href="his_admin_assaign_dept.php">Assign Department</a>
        </li>
        <li>
            <a href="his_admin_transfer_employee.php">Transfer Employee</a>
        </li>
    </ul>
</li>

                            <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="mdi mdi-pill text-2xl text-teal-500"></i>
        <span class="font-medium text-black ml-2"> Pharmacy </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_add_pharm_cat.php">Add Pharm Category</a>
        </li>
        <li>
            <a href="his_admin_view_pharm_cat.php">View Pharm Category</a>
        </li>
        <li>
            <a href="his_admin_manage_pharm_cat.php">Manage Pharm Category</a>
        </li>
        <hr class="my-1 border-gray-200">
        <li>
            <a href="his_admin_add_pharmaceuticals.php">Add Pharmaceuticals</a>
        </li>
        <li>
            <a href="his_admin_view_pharmaceuticals.php">View Pharmaceuticals</a>
        </li>
        <li>
            <a href="his_admin_manage_pharmaceuticals.php">Manage Pharmaceuticals</a>
        </li>
        <hr class="my-1 border-gray-200">
        <li>
            <a href="his_admin_add_presc.php">Dispense Drugs</a>
        </li>
        <li>
            <a href="his_admin_view_presc.php">View Dispense Drugs</a>
        </li>
        <li>
            <a href="his_admin_manage_presc.php">Manage Dispense Drug</a>
        </li>
    </ul>
</li>

                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="fas fa-file-invoice-dollar text-2xl text-amber-500"></i>
        <span class="font-medium text-black ml-2"> Billing </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_add_bill.php">Add Bill</a>
        </li>
        <li>
            <a href="his_admin_billing.php">Manage Bills</a>
        </li>
        <li>
            <a href="his_admin_billing_settings.php">Billing Settings</a>
        </li>
    </ul>
</li>
                            <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="mdi mdi-cash-multiple text-2xl text-emerald-500"></i>
        <span class="font-medium text-black ml-2"> Accounting </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_add_acc.payable.php">Add Acc. Payable</a>
        </li>
        <li>
            <a href="his_admin_manage_acc_payable.php">Manage Acc. Payable</a>
        </li>
        <hr class="my-1 border-gray-200">
        <li>
            <a href="his_admin_add_acc_receivable.php">Add Acc. Receivable</a>
        </li>
        <li>
            <a href="his_admin_manage_acc_receivable.php">Manage Acc. Receivable</a>
        </li>
    </ul>
</li>
                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="fas fa-funnel-dollar text-2xl text-indigo-500"></i>
        <span class="font-medium text-black ml-2"> Inventory </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_pharm_inventory.php">Pharmaceuticals</a>
        </li>
        <li>
            <a href="his_admin_equipments_inventory.php">Assets</a>
        </li>
    </ul>
</li>
                
                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="fe-share text-2xl text-rose-500"></i>
        <span class="font-medium text-black ml-2"> Reporting </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_inpatient_records.php">InPatient Records</a>
        </li>
        <li>
            <a href="his_admin_outpatient_records.php">OutPatient Records</a>
        </li>
        <li>
            <a href="his_admin_employee_records.php">Employee Records</a>
        </li>
        <li>
            <a href="his_admin_pharmaceutical_records.php">Pharmaceutical Records</a>
        </li>
        <li>
            <a href="his_admin_accounting_records.php">Accounting Records</a>
        </li>
        <li>
            <a href="his_admin_medical_records.php">Medical Records</a>
        </li>
    </ul>
</li><li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="fe-file-text text-2xl text-sky-500"></i>
        <span class="font-medium text-black ml-2"> Medical Records </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_add_medical_record.php">Add Medical Record</a>
        </li>
        <li>
            <a href="his_admin_manage_medical_record.php">Manage Medical Records</a>
        </li>
    </ul>
</li>
                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="mdi mdi-flask text-2xl text-purple-500"></i>
        <span class="font-medium text-black ml-2"> Laboratory </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_patient_lab_test.php">Patient Lab Tests</a>
        </li>
        <li>
            <a href="his_admin_patient_lab_result.php">Patient Lab Results</a>
        </li>
        <li>
            <a href="his_admin_patient_lab_vitals.php">Patient Vitals</a>
        </li>
        <li>
            <a href="his_admin_employee_lab_vitals.php">Employee Vitals</a>
        </li>
        <li>
            <a href="his_admin_lab_report.php">Lab Reports</a>
        </li>
        <hr class="my-1 border-gray-200">
        <li>
            <a href="his_admin_add_lab_equipment.php">Add Lab Equipment</a>
        </li>
        <li>
            <a href="his_admin_manage_lab_equipment.php">Manage Lab Equipments</a>
        </li>
    </ul>
</li>
                          <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="mdi mdi-scissors-cutting text-2xl text-slate-600"></i>
        <span class="font-medium text-black ml-2"> Surgical / Theatre </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_add_equipment.php">Add Equipment</a>
        </li>
        <li>
            <a href="his_admin_manage_equipment.php">Manage Equipments</a>
        </li>
        <li>
            <a href="his_admin_add_theatre_patient.php">Add Patient</a>
        </li>
        <li>
            <a href="his_admin_manage_theatre_patient.php">Manage Patients</a>
        </li>
        <li>
            <a href="his_admin_surgery_records.php">Surgery Records</a>
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
            <a href="his_admin_add_payroll.php">Add Payroll</a>
        </li>
        <li>
            <a href="his_admin_manage_payrolls.php">Manage Payrolls</a>
        </li>
        <li>
            <a href="his_admin_generate_payrolls.php">Generate Payrolls</a>
        </li>
    </ul>
</li>

                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="fas fa-user-tag text-2xl text-slate-500"></i>
        <span class="font-medium text-black ml-2"> Vendors </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_add_vendor.php">Add Vendor</a>
        </li>
        <li>
            <a href="his_admin_manage_vendor.php">Manage Vendors</a>
        </li>
    </ul>
</li>
                           <li>
    <a href="javascript: void(0);" class="waves-effect flex items-center font-sans">
        <i class="fas fa-lock text-2xl text-orange-500"></i>
        <span class="font-medium text-black ml-2"> Password Resets </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="his_admin_manage_password_resets.php">Manage Resets</a>
        </li>
    </ul>
</li>

                        </ul>

                    </div>
                    <!-- End Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->

            </div>