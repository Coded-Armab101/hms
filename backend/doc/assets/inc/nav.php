<?php
    $doc_id = $_SESSION['doc_id'];
    $doc_number = $_SESSION['doc_id'];
    $ret="SELECT * FROM  his_docs WHERE doc_id = ? AND doc_id = ?";
    $stmt= $mysqli->prepare($ret) ;
    $stmt->bind_param('is',$doc_id, $doc_number);
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
        
        <button class="button-menu-mobile text-dark border-0 bg-transparent p-2 rounded-lg hover:bg-light transition-all mr-3">
            <i class="fe-menu" style="font-size: 1.5rem;"></i>
        </button>

        <div class="dropdown d-none d-lg-block">
            <button class="btn btn-link text-dark font-weight-bold text-decoration-none dropdown-toggle px-3 py-2 rounded-pill hover:bg-light transition-all" 
                    data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" style="font-size: 14px;">
               View
                <i class="mdi mdi-chevron-down ml-1 text-primary"></i>
            </button>
            
            <div class="dropdown-menu border-0 shadow-lg rounded-xl py-2 mt-2 animate-up">
                <a href="his_doc_view_patients.php" class="dropdown-item d-flex align-items-center px-4 py-2 text-secondary">
                    <div class="bg-soft-success p-2 rounded mr-3">
                        <i class="fe-activity text-success"></i>
                    </div>
                    <span class="font-weight-medium">View Patients</span>
                </a>
                <a href="his_doc_lab_report.php" class="dropdown-item d-flex align-items-center px-4 py-2 text-secondary">
                    <div class="bg-soft-info p-2 rounded mr-3">
                        <i class="fe-hard-drive text-info"></i>
                    </div>
                    <span class="font-weight-medium">Laboratory Report</span>
                </a>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center">
        
        <form class="d-none d-md-flex align-items-center position-relative mr-4">
            <input type="text" 
                   class="form-control bg-light border-0 rounded-pill px-4 py-2" 
                   style="width: 280px; font-size: 13px; color: #333;"
                   placeholder="Search records, patients...">
            <button class="btn position-absolute border-0" style="right: 5px; top: 50%; transform: translateY(-50%);" type="submit">
                <i class="fe-search text-muted"></i>
            </button>
        </form>

        <div class="dropdown">
            <button class="btn d-flex align-items-center p-1 rounded-pill hover:bg-light transition-all dropdown-toggle border-0" 
                    data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <div class="text-right mr-2 d-none d-sm-block">
                    <p class="mb-0 font-weight-bold text-dark" style="font-size: 13px; line-height: 1;">
                        <?php echo $row->doc_fname . ' ' . $row->doc_lname; ?>
                    </p>
                    <small class="text-muted font-weight-medium">Medical Doctor</small>
                </div>
                <div class="position-relative">
                    <img src="assets/images/users/<?php echo $row->doc_dpic;?>" 
                         alt="User" 
                         class="rounded-circle border-2 border-primary"
                         style="width: 38px; height: 38px; object-fit: cover; border: 2px solid #727cf5;">
                    <span class="position-absolute" style="height: 10px; width: 10px; background-color: #2ecc71; border: 2px solid #fff; border-radius: 50%; bottom: 2px; right: 2px;"></span>
                </div>
            </button>

            <div class="dropdown-menu dropdown-menu-right border-0 shadow-lg rounded-xl py-2 mt-2 w-64">
                <div class="dropdown-header border-bottom mb-2 py-3 bg-light rounded-top">
                    <h6 class="text-uppercase font-weight-bold m-0 text-dark" style="letter-spacing: 0.5px;">Account Settings</h6>
                </div>
                
                <a href="his_doc_update-account.php" class="dropdown-item d-flex align-items-center px-4 py-2 text-secondary">
                    <i class="fas fa-user-tag mr-3 text-primary opacity-75"></i> 
                    <span>Update Account</span>
                </a>

                <div class="dropdown-divider border-light"></div>
                
                <a href="his_doc_logout_partial.php" class="dropdown-item d-flex align-items-center px-4 py-2 text-danger font-weight-bold">
                    <i class="fe-log-out mr-3"></i> 
                    <span>Logout System</span>
                </a>
            </div>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<?php }?>