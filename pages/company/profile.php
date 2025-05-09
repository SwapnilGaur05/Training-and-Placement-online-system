<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ../login.php');
    exit;
}

// Get company information
$userId = $_SESSION['user_id'];
$companyQuery = "SELECT c.*, u.email FROM companies c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.user_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    header('Location: ../logout.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $name = trim($_POST['name']);
    $contactPerson = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $website = trim($_POST['website']);
    $address = trim($_POST['address']);
    $industry = trim($_POST['industry']);
    $about = trim($_POST['about']);
    
    // Validate data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Company name is required";
    }
    
    if (empty($contactPerson)) {
        $errors[] = "Contact person name is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // Check if the columns exist in the companies table
        $columnCheck = "SHOW COLUMNS FROM companies";
        $columnResult = $conn->query($columnCheck);
        $existingColumns = [];
        
        while ($col = $columnResult->fetch_assoc()) {
            $existingColumns[] = $col['Field'];
        }
        
        // Build dynamic update query based on existing columns
        $updateFields = [];
        $paramTypes = "";
        $paramValues = [];
        
        // Always update name and contact_person
        $updateFields[] = "name = ?";
        $paramTypes .= "s";
        $paramValues[] = $name;
        
        $updateFields[] = "contact_person = ?";
        $paramTypes .= "s";
        $paramValues[] = $contactPerson;
        
        // Conditionally add other fields if they exist
        if (in_array('phone', $existingColumns)) {
            $updateFields[] = "phone = ?";
            $paramTypes .= "s";
            $paramValues[] = $phone;
        }
        
        if (in_array('website', $existingColumns)) {
            $updateFields[] = "website = ?";
            $paramTypes .= "s";
            $paramValues[] = $website;
        }
        
        if (in_array('address', $existingColumns)) {
            $updateFields[] = "address = ?";
            $paramTypes .= "s";
            $paramValues[] = $address;
        }
        
        if (in_array('industry', $existingColumns)) {
            $updateFields[] = "industry = ?";
            $paramTypes .= "s";
            $paramValues[] = $industry;
        }
        
        if (in_array('about', $existingColumns)) {
            $updateFields[] = "about = ?";
            $paramTypes .= "s";
            $paramValues[] = $about;
        }
        
        if (in_array('updated_at', $existingColumns)) {
            $updateFields[] = "updated_at = NOW()";
        }
        
        // Add user_id for WHERE clause
        $paramTypes .= "i";
        $paramValues[] = $userId;
        
        // Build and execute the update query
        $updateQuery = "UPDATE companies SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param($paramTypes, ...$paramValues);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
            
            // Handle logo upload if file was submitted
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (in_array($_FILES['logo']['type'], $allowedTypes) && $_FILES['logo']['size'] <= $maxSize) {
                    // Create directory if it doesn't exist
                    $uploadDir = '../../assets/images/company_logos/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = $userId . '_' . time() . '_' . basename($_FILES['logo']['name']);
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $filePath)) {
                        // Update logo path in database
                        $relativePath = 'assets/images/company_logos/' . $fileName;
                        
                        if (in_array('logo', $existingColumns)) {
                            $logoQuery = "UPDATE companies SET logo = ? WHERE user_id = ?";
                            $stmt = $conn->prepare($logoQuery);
                            $stmt->bind_param("si", $relativePath, $userId);
                            $stmt->execute();
                        } else {
                            $_SESSION['warning'] = "Logo uploaded but database update skipped - 'logo' column does not exist.";
                        }
                    } else {
                        $_SESSION['error'] = "Failed to upload logo. Please try again.";
                    }
                } else {
                    $_SESSION['error'] = "Invalid file type or size. Please upload a JPEG, PNG, or GIF image under 2MB.";
                }
            }
            
            // Refresh company data
            $stmt = $conn->prepare($companyQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $company = $stmt->get_result()->fetch_assoc();
        } else {
            $_SESSION['error'] = "Failed to update profile. Please try again.";
        }
    }
}

// Set page title
$pageTitle = 'Company Profile';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Company Profile</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['warning'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Check for necessary columns in companies table
                    $missingColumns = [];
                    $requiredColumns = ['phone', 'website', 'address', 'industry', 'about', 'logo', 'updated_at'];
                    
                    $columnCheck = "SHOW COLUMNS FROM companies";
                    $columnResult = $conn->query($columnCheck);
                    $existingColumns = [];
                    
                    while ($col = $columnResult->fetch_assoc()) {
                        $existingColumns[] = $col['Field'];
                    }
                    
                    foreach ($requiredColumns as $column) {
                        if (!in_array($column, $existingColumns)) {
                            $missingColumns[] = $column;
                        }
                    }
                    
                    if (!empty($missingColumns)):
                    ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">Database Structure Issue</h5>
                        <p>Your database is missing the following columns needed for the company profile: <strong><?php echo implode(', ', $missingColumns); ?></strong></p>
                        <p>Please run the <a href="../../update_companies_table.php" class="alert-link">database update script</a> to fix this issue.</p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <div class="profile-img-container mx-auto" style="width: 150px; height: 150px; overflow: hidden; position: relative;">
                                        <?php if (!empty($company['logo'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($company['logo']); ?>" 
                                                 class="img-fluid rounded" id="preview-logo" 
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="border rounded d-flex align-items-center justify-content-center bg-light" 
                                                 style="width: 100%; height: 100%;" id="logo-placeholder">
                                                <i class="fas fa-building fa-3x text-secondary"></i>
                                            </div>
                                            <img src="" class="img-fluid rounded d-none" id="preview-logo" 
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3">
                                        <label for="logo" class="btn btn-outline-primary">
                                            <i class="fas fa-upload me-2"></i>Change Logo
                                        </label>
                                        <input type="file" name="logo" id="logo" class="d-none" accept="image/*">
                                    </div>
                                    <small class="text-muted d-block mt-2">Recommended size: 400x400 pixels</small>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($company['name']); ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                                   value="<?php echo htmlspecialchars($company['contact_person']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" 
                                                   value="<?php echo isset($company['email']) ? htmlspecialchars($company['email']) : ''; ?>" readonly>
                                            <small class="text-muted">Contact admin to change email</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo isset($company['phone']) ? htmlspecialchars($company['phone']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="website" class="form-label">Website</label>
                                            <input type="url" class="form-control" id="website" name="website" 
                                                   value="<?php echo isset($company['website']) ? htmlspecialchars($company['website']) : ''; ?>" 
                                                   placeholder="https://example.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo isset($company['address']) ? htmlspecialchars($company['address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="industry" class="form-label">Industry</label>
                            <input type="text" class="form-control" id="industry" name="industry" 
                                   value="<?php echo isset($company['industry']) ? htmlspecialchars($company['industry']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="about" class="form-label">About the Company</label>
                            <textarea class="form-control" id="about" name="about" rows="5"><?php echo isset($company['about']) ? htmlspecialchars($company['about']) : ''; ?></textarea>
                            <small class="text-muted">Describe your company, mission, values, and what you're looking for in candidates.</small>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview logo image before upload
    const logoInput = document.getElementById('logo');
    const previewLogo = document.getElementById('preview-logo');
    const logoPlaceholder = document.getElementById('logo-placeholder');
    
    logoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewLogo.src = e.target.result;
                previewLogo.classList.remove('d-none');
                
                if (logoPlaceholder) {
                    logoPlaceholder.classList.add('d-none');
                }
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 