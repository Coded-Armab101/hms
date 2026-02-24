<?php
    $lab_id = $_SESSION['lab_id'];
    $lab_email = $_SESSION['lab_email'];
    $ret="SELECT * FROM  his_lab_scist WHERE lab_id = ? AND lab_email = ?";
    $stmt= $mysqli->prepare($ret) ;
    $stmt->bind_param('is', $lab_id, $lab_email);
    $stmt->execute() ;//ok
    $res=$stmt->get_result();
    //$cnt=1;
    while($row=$res->fetch_object())
    {
?>
    <nav class="bg-white fixed w-full z-50 top-0 left-0 h-16 d-flex align-items-center justify-content-between px-4 shadow-sm border-bottom border-light">
    
    <div class="d-flex align-items-center">
        <a href="his_admin_dashboard.php" class="mr-4 d-flex align-items-center">
            <img src="assets/images/logo.png" alt="Logo" style="height: 32px; width: auto;">
        </a>
        
        <button class="button-menu-mobile text-dark border-0 bg-transparent p-2 rounded-lg hover:bg-light transition-all mr-3 shadow-none">
            <i class="fe-menu" style="font-size: 1.5rem;"></i>
        </button>

        <div class="dropdown d-none d-lg-block">
            <button class="btn btn-link text-dark font-weight-bold text-decoration-none dropdown-toggle px-3 py-2 rounded-pill hover:bg-light transition-all shadow-none" 
                    data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" style="font-size: 14px;">
               <i class="fe-plus-circle text-primary mr-1"></i> Create New
               <i class="mdi mdi-chevron-down ml-1 text-muted"></i>
            </button>
            
            <div class="dropdown-menu border-0 shadow-lg rounded-xl py-2 mt-2">
                <a href="his_doc_register_patient.php" class="dropdown-item d-flex align-items-center px-4 py-2">
                    <div class="p-2 rounded mr-3" style="background-color: #e1fcef;">
                        <i class="fe-user-plus text-success"></i>
                    </div>
                    <span class="font-weight-medium text-dark">Register Patient</span>
                </a>
                <a href="his_doc_lab_report.php" class="dropdown-item d-flex align-items-center px-4 py-2">
                    <div class="p-2 rounded mr-3" style="background-color: #e0f4ff;">
                        <i class="fe-file-text text-info"></i>
                    </div>
                    <span class="font-weight-medium text-dark">Laboratory Report</span>
                </a>
                <div class="dropdown-divider"></div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center">
        
        <form class="d-none d-md-flex align-items-center position-relative mr-4" action="#">
            <input type="text" 
                   class="form-control bg-light border-0 rounded-pill px-4 py-2 shadow-none" 
                   style="width: 260px; font-size: 13px; color: #333;"
                   placeholder="Search records...">
            <button class="btn position-absolute border-0" style="right: 5px; top: 50%; transform: translateY(-50%);" type="submit">
                <i class="fe-search text-muted"></i>
            </button>
        </form>

        <div class="dropdown">
            <button class="btn d-flex align-items-center p-1 rounded-pill hover:bg-light transition-all dropdown-toggle border-0 shadow-none" 
                    data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                
                <div class="text-right mr-2 d-none d-sm-block">
                    <p class="mb-0 font-weight-bold text-dark" style="font-size: 13px; line-height: 1;">
                        <?php echo $row->lab_fname . ' ' . $row->lab_lname; ?>
                    </p>
                    <small class="text-muted font-weight-medium">Medical Staff</small>
                </div>

                <div class="position-relative">
                    <img src="assets/images/users/<?php echo $row->lab_dpic;?>" 
                         alt="User" 
                         class="rounded-circle"
                         style="width: 38px; height: 38px; object-fit: cover; border: 2px solid #727cf5;">
                    <span class="position-absolute" style="height: 10px; width: 10px; background-color: #2ecc71; border: 2px solid #fff; border-radius: 50%; bottom: 2px; right: 2px;"></span>
                </div>
            </button>

            <div class="dropdown-menu dropdown-menu-right border-0 shadow-lg rounded-xl py-2 mt-2" style="min-width: 200px;">
                <div class="dropdown-header noti-title">
                    <h6 class="text-overflow m-0">Welcome !</h6>
                </div>
                
                <div class="dropdown-divider"></div>

                <a href="his_doc_logout_partial.php" class="dropdown-item d-flex align-items-center px-4 py-2 text-danger font-weight-bold">
                    <i class="fe-log-out mr-3"></i> 
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<?php }?>