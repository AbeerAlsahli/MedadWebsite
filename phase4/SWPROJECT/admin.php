<?php
require_once 'config.php';
$message = "";
$messageType = "";

// ====================== EVENTS & CLUBS CRUD ========================

// ADD ITEM
// ADD ITEM
if (isset($_POST['add_item'])) {
    $type = $_POST['item_type'];
    
    // Handle image upload
    $image_path = $type === 'event' ? 'image/event4.jpg' : 'image/event3.jpg'; // Default images
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }
    
    if ($type === 'event') {
        $stmt = $pdo->prepare("INSERT INTO event (title, date, location, description, capacity, image_path) VALUES (?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $_POST['title'],
            $_POST['date'],
            $_POST['location'],
            $_POST['description'],
            $_POST['capacity'],
            $image_path
        ]);
        $message = $success ? "تمت إضافة الفعالية بنجاح" : "فشل إضافة الفعالية";
    } elseif ($type === 'club') {
        $stmt = $pdo->prepare("INSERT INTO club (name, description, location, image_path) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['location'],
            $image_path
        ]);
        $message = $success ? "تمت إضافة النادي بنجاح" : "فشل إضافة النادي";
    }
    $messageType = $success ? "success" : "error";
}

// EDIT ITEM by name
// EDIT ITEM by name
if (isset($_POST['edit_item'])) {
    $type = $_POST['edit_item_type'];
    $name = $_POST['item_to_edit'];
    
    // Handle image upload for edit
    $image_path = null; // Don't update image if no new file uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }
    
    // Build the update query dynamically for non-empty fields
    $update_fields = [];
    $update_values = [];
    
    $new_title = $_POST['title'];
    $new_date = $_POST['date'];
    $new_location = $_POST['location'];
    $new_description = $_POST['description'];
    $new_capacity = $_POST['capacity'];

    if ($type === 'event') {
        if (!empty($new_title)) {$update_fields[] = "title=?"; $update_values[] = $new_title;}
        if (!empty($new_date)) {$update_fields[] = "date=?"; $update_values[] = $new_date;}
        if (!empty($new_location)) {$update_fields[] = "location=?"; $update_values[] = $new_location;}
        if (!empty($new_description)) {$update_fields[] = "description=?"; $update_values[] = $new_description;}
        if ($new_capacity !== '' && $new_capacity !== null) {$update_fields[] = "capacity=?"; $update_values[] = $new_capacity;}
        if ($image_path) {$update_fields[] = "image_path=?"; $update_values[] = $image_path;}

        if (!empty($update_fields)) {
            $sql = "UPDATE event SET " . implode(", ", $update_fields) . " WHERE title=?";
            $update_values[] = $name;
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($update_values);
            $message = $success ? "تم تعديل الفعالية بنجاح" : "فشل تعديل الفعالية";
        } else {
            $success = true;
            $message = "لم يتم تعديل أي بيانات";
        }

    } elseif ($type === 'club') {
        if (!empty($new_title)) {$update_fields[] = "name=?"; $update_values[] = $new_title;}
        if (!empty($new_location)) {$update_fields[] = "location=?"; $update_values[] = $new_location;}
        if (!empty($new_description)) {$update_fields[] = "description=?"; $update_values[] = $new_description;}
        if ($image_path) {$update_fields[] = "image_path=?"; $update_values[] = $image_path;}

        if (!empty($update_fields)) {
            $sql = "UPDATE club SET " . implode(", ", $update_fields) . " WHERE name=?";
            $update_values[] = $name;
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($update_values);
            $message = $success ? "تم تعديل النادي بنجاح" : "فشل تعديل النادي";
        } else {
            $success = true;
            $message = "لم يتم تعديل أي بيانات";
        }
    }
    $messageType = $success ? "success" : "error";
}

// DELETE ITEM by name
if (isset($_POST['delete_item'])) {
    $type = $_POST['delete_item_type'];
    $name = $_POST['item_to_delete'];
    if ($type === 'event') {
        $stmt = $pdo->prepare("DELETE FROM event WHERE title=?");
        $success = $stmt->execute([$name]);
        $message = $success ? "تم حذف الفعالية بنجاح" : "فشل حذف الفعالية";
    } elseif ($type === 'club') {
        $stmt = $pdo->prepare("DELETE FROM club WHERE name=?");
        $success = $stmt->execute([$name]);
        $message = $success ? "تم حذف النادي بنجاح" : "فشل حذف النادي";
    }
    $messageType = $success ? "success" : "error";
}

// fetch
$events = $pdo->query("SELECT * FROM event ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$clubs = $pdo->query("SELECT * FROM club ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html> 
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مِـداد | Medad</title>

  <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
  
  <style>
    @font-face {
        font-family: 'Doran';
        src: url('font/Doran-Regular.woff2') format('woff2'),
             url('font/Doran-Regular.woff') format('woff'),
             url('font/Doran-Regular.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    
    @font-face {
        font-family: 'Doran';
        src: url('font/Doran-Bold.woff2') format('woff2'),
             url('font/Doran-Bold.woff') format('woff'),
             url('font/Doran-Bold.ttf') format('truetype');
        font-weight: bold;
        font-style: normal;
        font-display: swap;
    }

    :root {
        --accent: #E8D1A7;
        --burgundy: #7B1E3A;
        --burgundy-light: rgba(123, 30, 58, 0.08);
        --text-dark: #2D2D2D;
        --text-light: #555555;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 6px 18px rgba(123, 30, 58, 0.15);
        --error: #7B1E3A;
        --error-light: rgba(123, 30, 58, 0.1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Almarai', 'Arial', sans-serif;
    }

    body {
        background: #f8f8f8;
        color: #333;
        line-height: 1.6;
        overflow-x: hidden;
        min-height: 100vh;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .background-art {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background: linear-gradient(rgba(248, 248, 248, 0.9), rgba(248, 248, 248, 0.9)), 
                    url('image/BACKGROUND.png') center/cover no-repeat;
        opacity: 0.3;
    }

    /* الهيدر  */
    .main-header{
      background: transparent;
      padding: 12px 0;
      margin-top: 10px;
      margin-bottom: 10px;
    }

    .header-shell{
      width: min(1100px, calc(100% - 24px));
      margin: 0 auto;
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: 10px;
      background: #fff;
      border-radius: 999px;
      padding: 8px 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      position: relative;
      z-index: 100;
    }

    .hamburger {
        font-size: 28px;
        cursor: pointer;
        color: var(--burgundy);
        background: none;
        border: none;
        justify-self: start;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
    }

    .hamburger:hover {
        background: var(--burgundy-light);
        transform: scale(1.1);
    }

    .header-center {
      justify-self: center;
    }

    .brand-image {
      width: 40px;
      height: 40px;
      object-fit: contain;
      background: transparent;
      display: inline-block;
    }

    @media (max-width: 600px){
      .header-shell{ 
        grid-template-columns: auto 1fr auto;
        padding: 6px 10px;
      }
      .brand-image {
        width: 35px;
        height: 35px;
      }
      .hamburger {
        width: 40px;
        height: 40px;
        font-size: 24px;
      }
    }

    .nav-menu {
      display: none;
      position: fixed;
      top: 0;
      right: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      text-align: center;
      transition: all 0.3s ease;
    }

    .nav-menu.show {
      display: flex;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .menu-content {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      padding: 40px 30px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(123, 30, 58, 0.1);
      min-width: 300px;
    }

    .menu-content a {
      display: block;
      font-size: 22px;
      color: var(--burgundy);
      text-decoration: none;
      margin: 20px 0;
      font-weight: 600;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
      padding: 12px 25px;
      border-radius: 12px;
    }

    .menu-content a:hover {
      color: white;
      background: var(--burgundy);
      transform: translateX(-10px);
      box-shadow: 0 5px 15px rgba(123, 30, 58, 0.3);
    }

    .close-btn {
      position: absolute;
      top: 25px;
      left: 25px;
      font-size: 32px;
      background: rgba(255, 255, 255, 0.9);
      border: none;
      color: var(--burgundy);
      cursor: pointer;
      transition: all 0.3s ease;
      padding: 10px;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .close-btn:hover {
      background: var(--burgundy);
      color: white;
      transform: scale(1.1);
    }

    /* المحتوى الرئيسي  */
    .admin-main {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
      max-width: 1200px;
      margin: 0 auto;
      flex: 1;
      width: 100%;
    }

    .admin-title {
      font-family: 'Doran', 'Arial', sans-serif;
      font-size: 2.2rem;
      color: var(--burgundy);
      margin-bottom: 30px;
      text-align: center;
      position: relative;
    }

    .admin-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      right: 25%;
      width: 50%;
      height: 3px;
      background: linear-gradient(90deg, transparent, var(--burgundy), transparent);
    }

    .admin-action-buttons {
      display: flex;
      gap: 20px;
      margin-bottom: 40px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .admin-action-buttons button {
      background: linear-gradient(135deg, var(--burgundy), #5a152c);
      color: white;
      border: none;
      padding: 14px 25px;
      border-radius: 14px;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Doran', 'Arial', sans-serif;
      box-shadow: var(--shadow);
      min-width: 180px;
    }

    .admin-action-buttons button:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-hover);
    }

    .admin-event-form {
      display: none;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 30px;
      width: 100%;
      max-width: 600px;
      margin-top: 20px;
      box-shadow: var(--shadow);
      border: 1px solid rgba(123, 30, 58, 0.1);
      position: relative;
      overflow: hidden;
    }

    .admin-event-form::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--burgundy), var(--accent), var(--burgundy));
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {
      0% , 100% { background-position: -200% 0; }
      50% { background-position: 200% 0; }
    }

    .form-title {
      font-family: 'Doran', 'Arial', sans-serif;
      font-size: 1.5rem;
      color: var(--burgundy);
      margin-bottom: 25px;
      text-align: center;
    }

    .form-group {
      margin-bottom: 25px;
      text-align: right;
    }

    .form-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      color: var(--text-dark);
      font-size: 1.1rem;
      position: relative;
    }

    .required-field::after {
      content: "*";
      color: var(--burgundy);
      margin-right: 5px;
      font-weight: bold;
    }

    .form-input, .form-textarea, .form-select {
      width: 100%;
      padding: 14px 18px;
      border: 2px solid rgba(123, 30, 58, 0.1);
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: white;
      font-family: 'Almarai', 'Arial', sans-serif;
    }

    .form-input:focus, .form-textarea:focus, .form-select:focus {
      outline: none;
      border-color: var(--burgundy);
      box-shadow: 0 0 0 4px rgba(123, 30, 58, 0.1);
    }

    .form-textarea {
      resize: vertical;
      min-height: 100px;
    }

    .date-time-group {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
    }

    .image-upload-container {
      border: 2px dashed rgba(123, 30, 58, 0.3);
      border-radius: 12px;
      padding: 25px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.5);
    }

    .image-upload-container:hover {
      border-color: var(--burgundy);
      background: rgba(123, 30, 58, 0.05);
    }

    .image-upload-icon {
      font-size: 2.5rem;
      color: var(--burgundy);
      margin-bottom: 10px;
    }

    .image-upload-text {
      color: var(--text-light);
      margin-bottom: 10px;
    }

    .image-preview {
      max-width: 100%;
      max-height: 200px;
      margin-top: 15px;
      border-radius: 8px;
      display: none;
    }

    .form-actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }

    .save-btn {
      background: linear-gradient(135deg, var(--burgundy), #5a152c);
      color: white;
      border: none;
      border-radius: 14px;
      padding: 14px 35px;
      font-size: 1.1rem;
      cursor: pointer;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
    }

    .save-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
    }

    .cancel-btn {
      background: transparent;
      color: var(--text-light);
      border: 2px solid var(--text-light);
      border-radius: 14px;
      padding: 14px 35px;
      font-size: 1.1rem;
      cursor: pointer;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
    }

    .cancel-btn:hover {
      background: var(--text-light);
      color: white;
      transform: translateY(-3px);
    }

    .delete-btn {
      background: linear-gradient(135deg, var(--burgundy), #5a152c);
      color: white;
      border: none;
      border-radius: 14px;
      padding: 14px 35px;
      font-size: 1.1rem;
      cursor: pointer;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
    }

    .delete-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
      background: linear-gradient(135deg, #5a152c, var(--burgundy));
    }

    .current-events-section {
      width: 100%;
      margin-top: 60px;
    }

    .section-title {
      font-family: 'Doran', 'Arial', sans-serif;
      font-size: 1.8rem;
      color: var(--burgundy);
      margin-bottom: 25px;
      text-align: center;
      position: relative;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      right: 25%;
      width: 50%;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--burgundy), transparent);
    }

    .events-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 25px;
      margin-top: 20px;
    }

    @media (max-width: 900px) {
      .events-container {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      }
    }

    @media (max-width: 600px) {
      .events-container {
        grid-template-columns: 1fr;
      }
    }

    .event-card {
      background-color: #fff;
      border-radius: 14px;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 400px;
    }

    .event-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-hover);
    }

    .event-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      display: block;
    }

    .card-content {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .event-card h3 {
      font-family: 'Doran', 'Arial', sans-serif;
      color: var(--burgundy);
      margin-bottom: 10px;
      font-size: 1.3rem;
      line-height: 1.3;
    }

    .event-card p {
      font-size: 0.95rem;
      line-height: 1.6;
      color: var(--text-light);
      margin-bottom: 15px;
      flex: 1;
    }

    .event-type {
      position: absolute;
      top: 10px;
      left: 10px;
      z-index: 2;
    }

    .event-type span {
      background-color: var(--burgundy);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.85rem;
      font-family: 'Almarai', 'Arial', sans-serif;
    }

    .event-details {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      background-color: #f9f9f9;
      border-radius: 8px;
      margin-top: 10px;
      padding: 0 12px;
      font-size: 0.85rem;
      color: var(--text-light);
    }
    
    .event-details.open {
        max-height: 300px;
        padding: 12px;
    }

    .details-btn {
      background-color: #f9f9f9;
      border: 1px solid rgba(123, 30, 58, 0.1);
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.85rem;
      cursor: pointer;
      width: 100%;
      text-align: center;
      color: var(--burgundy);
      margin-top: auto;
      transition: all 0.3s ease;
      font-family: 'Almarai', 'Arial', sans-serif;
    }

    .details-btn:hover {
      background-color: var(--burgundy-light);
    }
    
    .alert {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 600;
        width: 100%;
        max-width: 600px;
    }
    .alert.success { background: #d4efd8; color: #0b6b2f; }
    .alert.error { background: #ffdede; color: #7b1e3a; }

    /* الفوتر  */
    .main-footer {
      background-color: #ebe2db;
      width: 100%;
      padding: 30px 16px 16px;
      margin-top: auto;
    }

    .footer-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      max-width: 1100px;
      margin: 0 auto;
    }

    .footer-left, .footer-center, .footer-right {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .footer-left h3 {
      color: var(--burgundy);
      margin-bottom: 12px;
      font-size: 1.1rem;
    }

    .footer-icons {
      display: flex;
      gap: 12px;
      justify-content: center;
    }
    
    .icon-box {
      width: 40px; 
      height: 40px;
      color: var(--burgundy);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .icon-box:hover {
      background-color: var(--burgundy);
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
    }

    .footer-center img.footer-logo {
      width: 55px;
      height: 55px;
      object-fit: contain;
      margin-bottom: 8px;
    }
    
    .footer-center p {
      font-weight: 600;
      color: var(--burgundy);
      font-size: 1rem;
    }

    .footer-right {
      flex-direction: row;
      align-items: center;
      gap: 8px;
      color: var(--burgundy);
      font-weight: 600;
      font-size: 1rem;
    }
    .footer-right svg {
        width: 16px;
        height: 16px;
        stroke: var(--burgundy);
    }

    @media (max-width: 768px) {
      .admin-action-buttons {
        flex-direction: column;
        width: 100%;
      }
      .admin-action-buttons button { width: 100%; }
      .date-time-group { grid-template-columns: 1fr; }
      .form-actions { flex-direction: column; }
      .save-btn, .cancel-btn, .delete-btn { width: 100%; }
      .menu-content { min-width: 280px; padding: 30px 20px; }
      .menu-content a { font-size: 20px; padding: 10px 20px; }
    }
    
        .logout-modal {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(15px);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        text-align: center;
    }

    .logout-modal.show {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    .logout-content {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 40px 30px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(123, 30, 58, 0.2);
        min-width: 350px;
        max-width: 90%;
        position: relative;
        overflow: hidden;
    }

    .logout-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border: 2px solid transparent;
        border-radius: 20px;
        background: linear-gradient(45deg, var(--burgundy), transparent, var(--burgundy), transparent, var(--burgundy));
        background-size: 400% 400%;
        animation: borderAnimation 3s linear infinite;
        z-index: 1;
        pointer-events: none;
    }

    @keyframes borderAnimation {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    .logout-content::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        right: 2px;
        bottom: 2px;
        background: white;
        border-radius: 18px;
        z-index: 1;
        pointer-events: none;
    }

    .logout-content h3 {
        color: var(--burgundy);
        margin-bottom: 20px;
        font-family: 'Doran', 'Arial', sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
        position: relative;
        z-index: 2;
    }

    .logout-content p {
        color: var(--text-light);
        margin-bottom: 30px;
        font-size: 1.1rem;
        line-height: 1.6;
        position: relative;
        z-index: 2;
    }

    .logout-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    .confirm-logout-btn {
        background: linear-gradient(135deg, var(--burgundy), #5a152c);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        cursor: pointer;
        font-family: 'Doran', 'Arial', sans-serif;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
        min-width: 120px;
    }

    .confirm-logout-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
    }

    .cancel-logout-btn {
        background: transparent;
        color: var(--text-light);
        border: 2px solid var(--text-light);
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        cursor: pointer;
        font-family: 'Doran', 'Arial', sans-serif;
        transition: all 0.3s ease;
        min-width: 120px;
    }

    .cancel-logout-btn:hover {
        background: var(--text-light);
        color: white;
        transform: translateY(-3px);
    }

    @media (max-width: 768px) {
        .logout-content {
            min-width: 280px;
            padding: 30px 20px;
        }
        
        .logout-actions {
            flex-direction: column;
        }
        
        .confirm-logout-btn,
        .cancel-logout-btn {
            width: 100%;
        }
    }
  </style>
</head>
<body>

  <div class="background-art"></div>

  <header class="main-header">
    <div class="header-shell">   
      <div class="header-start">
        <img class="brand-image" src="image/LOGO SW.png" alt="Medad Logo">
      </div>
      
      <div class="header-center"></div>
      
      <button class="hamburger" id="hamburger">☰</button>
    </div>
  </header>

  <nav class="nav-menu" id="navMenu">
    <button class="close-btn" id="closeBtn">&times;</button>
    <div class="menu-content">
      <a href="adhome.php">الملف الشخصي</a>
      <a href="admin.php">إدارة الفعاليات والاندية</a>
      <a id="logoutLink">تسجيل خروج</a>
    </div>
  </nav>

  <main class="admin-main">
    <h1 class="admin-title">إدارة الفعاليات والأندية</h1>

    <?php if($message): ?>
    <div class="alert <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="admin-action-buttons">
      <button onclick="showForm('add')">إضافة فعالية/نادي</button>
      <button onclick="showForm('edit')">تعديل فعالية/نادي</button>
      <button onclick="showForm('delete')">حذف فعالية/نادي</button>
    </div>

    <div class="admin-event-form" id="form-add">
      <h2 class="form-title">إضافة فعالية/نادي جديد</h2>
      
      <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label for="add-type" class="form-label required-field">النوع</label>
            <select name="item_type" id="add-type" class="form-select" required onchange="toggleFields('add')">
              <option value="">اختر النوع</option>
              <option value="event">فعالية</option>
              <option value="club">نادي</option>
            </select>
          </div>

          <div class="form-group">
            <label for="add-name" class="form-label required-field">اسم الفعالية/النادي</label>
            <input type="text" name="title" id="add-name" class="form-input" placeholder="أدخل اسم الفعالية أو النادي" required>
          </div>

          <div class="form-group">
            <label for="add-description" class="form-label required-field">الوصف</label>
            <textarea name="description" id="add-description" class="form-textarea" placeholder="أدخل وصف الفعالية أو النادي" rows="4" required></textarea>
          </div>

          <div class="form-group" id="add-date-group" style="display:none;">
            <label class="form-label required-field">التاريخ</label>
            <div class="date-time-group">
              <input type="date" name="date" id="add-date" class="form-input">
            </div>
          </div>
          
          <div class="form-group" id="add-capacity-group" style="display:none;">
            <label class="form-label">السعة</label>
            <input type="number" name="capacity" class="form-input" placeholder="السعة" min="0">
          </div>

          <div class="form-group">
            <label for="add-location" class="form-label required-field">المكان</label>
            <input type="text" name="location" id="add-location" class="form-input" placeholder="أدخل مكان الفعالية" required>
          </div>

          <div class="form-group">
            <label class="form-label">صورة الفعالية/النادي</label>
            <div class="image-upload-container" id="add-image-upload">
              <div class="image-upload-icon">📷</div>
              <div class="image-upload-text">انقر لرفع صورة</div>
              <input type="file" name="image" id="add-image" accept="image/*" style="display: none;">
              <img id="add-image-preview" class="image-preview" alt="معاينة الصورة">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" name="add_item" class="save-btn">إضافة</button>
            <button type="button" class="cancel-btn" onclick="hideAllForms()">إلغاء</button>
          </div>
      </form>
    </div>

    <div class="admin-event-form" id="form-edit">
      <h2 class="form-title">تعديل فعالية/نادي</h2>
      
      <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
             <label class="form-label required-field">اختر النوع</label>
             <select name="edit_item_type" id="edit-type" class="form-select" required onchange="populateEditDropdown(); toggleFields('edit');">
               <option value="">-- اختر --</option>
               <option value="event">فعالية</option>
               <option value="club">نادي</option>
             </select>
          </div>

          <div class="form-group">
            <label for="edit-select" class="form-label required-field">اختر الفعالية/النادي</label>
            <select name="item_to_edit" id="edit-select" class="form-select" required>
              <option value="">اختر الفعالية/النادي أولاً</option>
            </select>
          </div>

          <div class="form-group">
            <label for="edit-name" class="form-label">اسم الفعالية/النادي (جديد)</label>
            <input type="text" name="title" id="edit-name" class="form-input" placeholder="أدخل الاسم الجديد">
          </div>

          <div class="form-group">
            <label for="edit-description" class="form-label">الوصف (جديد)</label>
            <textarea name="description" id="edit-description" class="form-textarea" placeholder="أدخل الوصف الجديد" rows="4"></textarea>
          </div>

          <div class="form-group" id="edit-date-group" style="display:none;">
            <label class="form-label">التاريخ (جديد)</label>
            <div class="date-time-group">
              <input type="date" name="date" id="edit-date" class="form-input">
            </div>
          </div>

          <div class="form-group" id="edit-capacity-group" style="display:none;">
             <label class="form-label">السعة (جديد)</label>
             <input type="number" name="capacity" class="form-input" placeholder="السعة الجديدة" min="0">
          </div>

          <div class="form-group">
            <label for="edit-location" class="form-label">المكان (جديد)</label>
            <input type="text" name="location" id="edit-location" class="form-input" placeholder="أدخل المكان الجديد">
          </div>

          <div class="form-group">
            <label class="form-label">صورة الفعالية/النادي</label>
            <div class="image-upload-container" id="edit-image-upload">
              <div class="image-upload-icon">📷</div>
              <div class="image-upload-text">انقر لتغيير الصورة</div>
              <input type="file" name="image" id="edit-image" accept="image/*" style="display: none;">
              <img id="edit-image-preview" class="image-preview" alt="معاينة الصورة">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" name="edit_item" class="save-btn">حفظ التغييرات</button>
            <button type="button" class="cancel-btn" onclick="hideAllForms()">إلغاء</button>
          </div>
      </form>
    </div>

    <div class="admin-event-form" id="form-delete">
      <h2 class="form-title">حذف فعالية/نادي</h2>
      
      <form method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
          <div class="form-group">
             <label class="form-label required-field">اختر النوع</label>
             <select name="delete_item_type" id="delete-type" class="form-select" required onchange="populateDeleteDropdown()">
               <option value="">-- اختر --</option>
               <option value="event">فعالية</option>
               <option value="club">نادي</option>
             </select>
          </div>

          <div class="form-group">
            <label for="delete-select" class="form-label required-field">اختر الفعالية/النادي</label>
            <select name="item_to_delete" id="delete-select" class="form-select" required>
              <option value="">اختر الفعالية/النادي أولاً</option>
            </select>
          </div>

          <div class="form-actions">
            <button type="submit" name="delete_item" class="delete-btn">حذف</button>
            <button type="button" class="cancel-btn" onclick="hideAllForms()">إلغاء</button>
          </div>
      </form>
    </div>

    <section class="current-events-section">
      <h2 class="section-title">الفعاليات والأندية الحالية</h2>
      <div class="events-container">
        
        <?php foreach(array_merge($events,$clubs) as $item): 
            $isEvent = isset($item['eventID']); // Assuming eventID exists for events based on standard DB structures, or check keys
            // If eventID isn't reliable, check keys
            if(!$isEvent && isset($item['title']) && isset($item['date'])) $isEvent = true;

            $title = $isEvent ? $item['title'] : $item['name'];
            $desc = $item['description'] ?? 'لا يوجد وصف';
            $location = $item['location'];
            $date = $item['date'] ?? null;
            $typeText = $isEvent ? 'فعالية' : 'نادي';
            
            // Using a static image placeholder as in the design file, since PHP doesn't have image data
            $imgSrc = $item['image_path'] ?? "image/event4.jpg";  
        ?>
        <div class="event-card">
          <div class="event-type">
            <span><?= $typeText ?></span>
          </div> 
          <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($title) ?>">
          <div class="card-content">
            <h3><?= htmlspecialchars($title) ?></h3>
            <p><?= htmlspecialchars(mb_strimwidth($desc, 0, 100, "...")) ?></p>
            <button class="details-btn" onclick="toggleDetails(this)">التفاصيل ▼</button>
            <div class="event-details">
              <p><strong>الموقع:</strong> <?= htmlspecialchars($location) ?></p>
              <?php if($isEvent): ?>
              <p><strong>التاريخ:</strong> <?= htmlspecialchars($date) ?></p>
              <?php endif; ?>
              <p><?= nl2br(htmlspecialchars($desc)) ?></p>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
      
         <div class="logout-modal" id="logoutModal">
  <div class="logout-content">
    <h3>تأكيد تسجيل الخروج</h3>
    <p>هل أنت متأكد من رغبتك في تسجيل الخروج من حسابك؟</p>
    <div class="logout-actions">
      <button class="confirm-logout-btn" id="confirmLogout">تأكيد</button>
      <button class="cancel-logout-btn" id="cancelLogout">إلغاء</button>
    </div>
  </div>
    </div>
    </section>
  </main>

  <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-left">
                 <h3>تواصل معنا</h3>
                <div class="footer-icons">
                    <a href="https://x.com/MedadKsu" class="icon-box" title="X">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                             <path d="M18.244 2H21L14.126 10.088 22 22h-5.905l-4.67-6.726L6.08 22H3l7.31-8.433L2 2h6.08l4.132 5.897L18.244 2zM7.118 4l9.764 13.997h1.999L9.12 4H7.118z"/>
                        </svg>
                    </a>
                    <a href="mailto:MedadKsu@gmail.com" class="icon-box" title="Email">
                         <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h16v16H4V4zm8 8l8-5H4l8 5zm0 2l-8-5v10h16V9l-8 5z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-center">
                <img src="image/LOGO SW.png" alt="شعار مداد" class="footer-logo">
                <p>© جميع الحقوق محفوظة 2025</p>
             </div>

            <div class="footer-right">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13401 2 5 5.13401 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13401 15.866 2 12 2Z"/>
                     <circle cx="12" cy="9" r="2.5"/>
                </svg>
                <span>المملكة العربية السعودية</span>
            </div>
        </div>
    </footer>

<script>
  // Transfer PHP data to JS for dropdowns
  const eventsData = <?= json_encode(array_column($events, 'title')) ?>;
  const clubsData = <?= json_encode(array_column($clubs, 'name')) ?>;

  const hamburger = document.getElementById('hamburger');
  const navMenu = document.getElementById('navMenu');
  const closeBtn = document.getElementById('closeBtn');

  hamburger.addEventListener('click', () => {
    navMenu.classList.add('show');
  });
  
  closeBtn.addEventListener('click', () => {
    navMenu.classList.remove('show');
  });

  function showForm(action) {
    const forms = ['form-add', 'form-edit', 'form-delete'];
    forms.forEach(id => document.getElementById(id).style.display = 'none');
    document.getElementById('form-' + action).style.display = 'block';
    
    // Reset selections when opening forms
    if(action === 'add') {
         document.getElementById('add-type').value = "";
         toggleFields('add');
    }
    if(action === 'edit') {
         document.getElementById('edit-type').value = "";
         document.getElementById('edit-select').innerHTML = '<option value="">اختر الفعالية/النادي أولاً</option>';
         toggleFields('edit');
    }
    if(action === 'delete') {
         document.getElementById('delete-type').value = "";
         document.getElementById('delete-select').innerHTML = '<option value="">اختر الفعالية/النادي أولاً</option>';
    }
  }

  function hideAllForms() {
    const forms = ['form-add', 'form-edit', 'form-delete'];
    forms.forEach(id => document.getElementById(id).style.display = 'none');
  }

  function toggleDetails(btn) {
      const details = btn.nextElementSibling;
      if (details.style.maxHeight && details.style.maxHeight !== "0px") {
          details.style.maxHeight = "0";
          details.style.padding = "0 12px";
          btn.innerHTML = "التفاصيل ▼";
      } else {
          details.style.maxHeight = "300px"; // approximate max height
          details.style.padding = "12px";
          btn.innerHTML = "إخفاء ▲";
      }
  }

// Logout functionality
logoutLink.addEventListener('click', (e) => {
  e.preventDefault();
  navMenu.classList.remove('show');
  logoutModal.classList.add('show');
});

confirmLogout.addEventListener('click', () => {
  window.location.href = 'index.html';
});

cancelLogout.addEventListener('click', () => {
  logoutModal.classList.remove('show');
});

logoutModal.addEventListener('click', (e) => {
  if (e.target === logoutModal) {
    logoutModal.classList.remove('show');
  }
});
  // Logic to show/hide Date/Capacity based on Event vs Club
  function toggleFields(formType) {
    let typeSelect, dateGroup, capacityGroup;
    
    if (formType === 'add') {
        typeSelect = document.getElementById('add-type');
        dateGroup = document.getElementById('add-date-group');
        capacityGroup = document.getElementById('add-capacity-group');
    } else {
        typeSelect = document.getElementById('edit-type');
        dateGroup = document.getElementById('edit-date-group');
        capacityGroup = document.getElementById('edit-capacity-group');
    }

    const selectedType = typeSelect.value;
    const isEvent = selectedType === 'event';

    if (dateGroup) dateGroup.style.display = isEvent ? 'block' : 'none';
    if (capacityGroup) capacityGroup.style.display = isEvent ? 'block' : 'none';
  }

  function populateDropdown(dropdownId, type) {
    const dropdown = document.getElementById(dropdownId);
    dropdown.innerHTML = '<option value="">-- اختر --</option>';

    const data = type === 'event' ? eventsData : clubsData;

    if (data && data.length > 0) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            dropdown.appendChild(option);
        });
    }
  }

  function populateEditDropdown() {
      const typeSelect = document.getElementById('edit-type');
      const type = typeSelect.value;
      if (type) {
          populateDropdown('edit-select', type);
      }
  }

  function populateDeleteDropdown() {
      const typeSelect = document.getElementById('delete-type');
      const type = typeSelect.value;
      if (type) {
          populateDropdown('delete-select', type);
      }
  }

  // Image Upload Visuals (UI only, since PHP handles text only for now)
  document.getElementById('add-image-upload').addEventListener('click', function() {
    document.getElementById('add-image').click();
  });
  document.getElementById('add-image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById('add-image-preview');
        preview.src = e.target.result;
        preview.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  });

  document.getElementById('edit-image-upload').addEventListener('click', function() {
    document.getElementById('edit-image').click();
  });
  document.getElementById('edit-image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById('edit-image-preview');
        preview.src = e.target.result;
        preview.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  });

</script>
</body>
</html>