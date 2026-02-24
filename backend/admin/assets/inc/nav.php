<?php
    $aid=$_SESSION['ad_id'];
    $ret="select * from his_admin where ad_id=?";
    $stmt= $mysqli->prepare($ret) ;
    $stmt->bind_param('i',$aid);
    $stmt->execute() ;//ok
    $res=$stmt->get_result();
    //$cnt=1;
    while($row=$res->fetch_object())
    {
?>
   <nav class="bg-white fixed w-full z-50 top-0 left-0 h-16 d-flex items-center justify-between px-4 shadow-lg">
    
    <div class="flex items-center gap-4">
        <a href="his_admin_dashboard.php" class="flex items-center">
            <img src="assets/images/logo.png" alt="Logo" class="h-10 w-auto brightness-0 invert">
        </a>
        
        <button class="button-menu-mobile text-black border font-bold border-gray-400 p-2 rounded-lg transition-colors">
            <i class="fe-menu text-2xl"></i>
        </button>

        <div class="hidden lg:block dropdown">
            <button class="flex items-center gap-1 text-black font-medium px-3 py-2 rounded-md  transition-all dropdown-toggle" 
                    data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                Create New
                <i class="mdi mdi-chevron-down ml-1"></i>
            </button>
            
            <div class="dropdown-menu absolute left-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-100 py-2">
                <a href="his_admin_employee_cat.php" class="dropdown-item flex items-center px-4 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="fe-users mr-3 text-indigo-500"></i> <span>Employee</span>
                </a>
                <a href="his_admin_register_patient.php" class="dropdown-item flex items-center px-4 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="fe-activity mr-3 text-emerald-500"></i> <span>Patient</span>
                </a>
                <a href="his_admin_add_payroll.php" class="dropdown-item flex items-center px-4 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="fe-layers mr-3 text-amber-500"></i> <span>Payroll</span>
                </a>
                <div class="dropdown-divider border-t border-slate-100 my-1"></div>
                <a href="his_admin_lab_report.php" class="dropdown-item flex items-center px-4 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="fe-hard-drive mr-3 text-sky-500"></i> <span>Lab Report</span>
                </a>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-6">
        
        <form class="hidden md:block flex items-center justify-between relative items-center">
            <input type="text" 
                   class="bg-gray-200 text-black placeholder-black text-lg rounded-xl px-4 py-2 w-64 border 0  transition-all" 
                   placeholder="Search records...">
            <button class="absolut right-3 top-2 text-black ml-2  " type="submit">
                <i class="fe-search"></i>
            </button>
        </form>

        <div class="dropdown">
            <button class="flex items-center gap-3 dropdown-toggle outline-none focus:outline-none" 
                    data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <div class="text-right hidden sm:block flex">
                  
                        <?php echo $row->ad_fname . ' ' . $row->ad_lname; ?>
                </div>
                <img src="assets/images/users/<?php echo $row->ad_dpic;?>" 
                     alt="User" 
                     class="w-10 h-10 rounded-full border-2 border-indigo-400 hover:border-white transition-all object-cover">
            </button>

            <div class="dropdown-menu dropdown-menu-right absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-2">
                <div class="px-4 py-2 border-b border-slate-50 md:hidden">
                     <p class="text-sm font-bold text-slate-800"><?php echo $row->ad_fname; ?></p>
                </div>
                <a href="his_admin_logout_partial.php" class="dropdown-item flex items-center px-3 py-1 text-rose-600 hover:bg-rose-50 font-medium">
                    <i class="fe-log-out mr-3"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="h-16"></div>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<?php }?>