<?php
// public/planner.php
$page_title = "Assistente de eBook & Gerador de Plano";

// Ensure utility functions like e() are available
if (!function_exists('e')) {
    function e($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/config/database.php'; 
require_once __DIR__ . '/../src/templates/header.php'; 

// Check if user has a Gemini API key stored (this logic should ideally be in auth or user model)
// For this example, we'll fetch it directly (assuming user_api_key is in the users table)
$has_gemini_api_key = false;
if (isset($_SESSION['user_id']) && $db) {
    $user_id = $_SESSION['user_id'];
    // Ensure the 'users' table and 'gemini_api_key' column exist in your database schema
    $stmt = $db->prepare("SELECT gemini_api_key FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($gemini_api_key);
        $stmt->fetch();
        $stmt->close();
        if (!empty($gemini_api_key)) {
            $has_gemini_api_key = true;
        }
    } else {
        // Handle potential database error or missing table/column
        error_log("Database query failed to check for gemini_api_key: " . $db->error);
        // You might want to display a user-friendly message here as well
    }
}

// This variable will be passed to the JavaScript
$has_gemini_api_key_php = $has_gemini_api_key ? 'true' : 'false';

?>

  <!-- The head content (title, meta, CSS links, CKEditor, SweetAlert2, FileSaver, jsPDF, html2canvas, Turndown, Marked, Bootstrap Icons)
       should ideally be handled by your header.php.
       Ensure your header.php includes Bootstrap CSS, Icons, CKEditor, SweetAlert2, FileSaver, jsPDF, html2canvas, Turndown, and Marked.
       If your header.php is minimal, you might need to add these links here *after* the header include.
       Assuming header.php includes Bootstrap CSS and Icons at least. -->

   <!-- Include these if NOT already in header.php -->
   <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
   <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"> -->
   <!-- <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script> -->
   <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
   <!-- <script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script> -->
   <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script> -->
   <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script> -->
   <!-- <script src="https://unpkg.com/turndown/dist/turndown.js"></script> -->
   <!-- <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script> -->
   <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> -->

  <!-- Custom CSS from original HTML -->
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      color: #343a40;
    }

    /* Header - Keep styles for elements within the header */
    /* The header HTML structure itself comes from header.php */
    .app-header {
      background-color: #ffffff;
      padding: 1rem 0;
      border-bottom: 1px solid #dee2e6;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      position: sticky;
      top: 0;
      z-index: 1020;
    }
    .app-header .logo {
      font-weight: 700;
      font-size: 1.5rem;
      color: #0d6efd;
      text-decoration: none;
    }
    .app-header .logo img {
      max-height: 40px;
      margin-right: 0.5rem;
    }
    .app-header h1 {
        font-size: 1.2rem; /* Reduced size */
        margin-left: 1rem;
        margin-bottom: 0;
        color: #495057;
        font-weight: 600;
        align-self: center;
    }
    /* Header Action Buttons */
    .header-actions button, .header-actions .dropdown > button {
        font-size: 0.85rem;
        margin-left: 0.5rem;
        padding: 0.3rem 0.8rem;
    }
    .header-actions .btn-outline-secondary svg,
    .header-actions .btn-danger svg,
    .header-actions .btn-outline-info svg,
    .header-actions .btn-outline-success svg {
        margin-right: 0.3rem;
        vertical-align: text-bottom;
    }
    .dropdown-menu .dropdown-header {
        font-weight: 600;
        color: #0d6efd;
    }
    .dropdown-menu .dropdown-item.subcategory-header {
        font-weight: 500;
        color: #495057;
        padding-left: 1.5rem;
        pointer-events: none; /* Not clickable */
    }
     .dropdown-menu .dropdown-item.template-item {
        padding-left: 2.5rem;
    }


    #apiKeyStatusContainer {
        background-color: #fff3cd;
        color: #664d03;
        padding: 0.75rem 1.25rem;
        border: 1px solid #ffecb5;
        margin-bottom: 1rem;
        text-align: center;
        font-size: 0.9rem; /* Slightly smaller */
    }
     #apiKeyStatusContainer.api-ok {
        background-color: #d1e7dd;
        color: #0f5132;
        border-color: #badbcc;
     }
     #apiKeyStatusContainer a {
         color: #583e02;
         text-decoration: underline;
     }
     #apiKeyStatusContainer.api-ok a {
         color: #0a3622;
     }


    /* Main Content */
    main {
      flex-grow: 1;
      padding-top: 1rem; /* Reduced due to sticky header */
      padding-bottom: 3rem;
    }

    /* Wizard Styles */
    .wizard-step { display: none; }
    .wizard-step.active {
      display: block;
      animation: fadeIn 0.4s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .step-title {
      font-size: 1.35rem;
      font-weight: 600;
      color: #343a40;
      margin-bottom: 1.8rem;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.75rem;
    }
    .form-control, .form-select, .btn {
      border-radius: 0.75rem;
    }
     .form-text {
        font-size: 0.85em;
        margin-top: -0.5rem; /* Adjusted spacing */
        margin-bottom: 1rem;
        color: #6c757d !important;
        display: block;
    }
     .form-check {
        margin-bottom: 0.5rem;
        padding-left: 2em;
     }
     .form-check-input {
         margin-left: -2em;
     }
     .form-check-label {
        font-weight: normal;
        color: #343a40;
     }
     .other-text-input {
         margin-top: 0.5rem;
         margin-left: 1.75rem;
         max-width: calc(100% - 1.75rem);
         display: none;
     }
     /* Validation Styling */
     .form-control.is-invalid, .form-select.is-invalid,
     .was-validated .form-control:invalid, .was-validated .form-select:invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
     }
      .invalid-feedback { display: none; } /* Hide default */
      .validation-error-message {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.5rem;
      }
      .required-field-marker { color: #dc3545; margin-left: 2px; }

    /* CKEditor Validation Styling */
    .ckeditor-wrapper-class.is-invalid-ckeditor .ck.ck-editor__main > .ck-editor__editable:not(.ck-focused) {
        border-color: #dc3545 !important;
    }
    .ck.ck-editor {
        margin-bottom: 1rem;
    }


    .wizard-card {
        background-color: #ffffff;
        padding: 2rem 2.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        margin-top: 1.5rem;
    }
    #progressIndicator {
        font-weight: 600;
        color: #6c757d;
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .navigation-buttons {
        margin-top: 2.5rem;
    }
    .btn-primary {
      background-color: #0d6efd;
      border: none;
      padding: 0.6rem 1.5rem;
    }
    .btn-outline-secondary {
      border-radius: 0.75rem;
      padding: 0.6rem 1.5rem;
    }
     .btn-success {
      padding: 0.6rem 1.5rem;
      border-radius: 0.75rem;
    }
    .btn-info .spinner-border, .btn-ai-action .spinner-border {
        width: 1em;
        height: 1em;
        border-width: .15em;
        display: none; /* Hide spinner initially */
    }
    .btn-info.loading .spinner-border, .btn-ai-action.loading .spinner-border {
        display: inline-block; /* Show spinner when loading */
    }
    .btn-info.loading span:not(.spinner-border),
    .btn-ai-action.loading span:not(.spinner-border),
    .btn-ai-action.loading svg { /* Hide text/icon when loading */
        display: none;
    }


    .ai-suggestions-container {
        background-color: #e9f5ff;
        border: 1px solid #bde0fe;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 0.75rem;
        font-size: 0.9rem;
    }
    .ai-suggestion-item {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        position: relative; /* For absolute positioning of applied feedback */
    }
    .ai-suggestion-item:last-child {
        margin-bottom: 0;
    }
    .ai-suggestion-item strong {
        display: block;
        margin-bottom: 0.25rem;
        color: #0d6efd;
    }
    .ai-suggestion-item p, .ai-suggestion-item pre {
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .ai-suggestion-item pre { /* For chapter list */
        background-color: transparent;
        border: none;
        padding: 0;
    }
    .btn-use-suggestion {
        font-size: 0.8rem;
        padding: 0.25rem 0.6rem;
    }
     /* Applied Feedback Style */
    .applied-feedback {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: rgba(40, 167, 69, 0.8);
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.75rem;
        font-weight: bold;
        animation: fadeOut 2s forwards;
        z-index: 1; /* Ensure it's above the button */
    }
    .inline-ai-button-container {
        text-align: right;
        margin-top: -0.5rem;
        margin-bottom: 0.75rem;
        display: flex; /* For aligning input and button */
        justify-content: flex-end;
        align-items: center;
        gap: 0.5rem;
    }
    .inline-ai-button-container .btn-ai-action {
        font-size: 0.8rem;
        padding: 0.25rem 0.6rem;
    }
    .ai-count-input {
        width: 60px;
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        text-align: center;
    }
    @keyframes fadeOut {
      0% { opacity: 1; }
      80% { opacity: 1; }
      100% { opacity: 0; }
    }


    /* Tooltip Style */
    .tooltip-inner {
      background-color: #343a40;
      color: #fff;
      font-size: 0.8rem;
      max-width: 250px;
      padding: 0.4rem 0.8rem;
      text-align: left;
    }
    .tooltip.bs-tooltip-top .tooltip-arrow::before { border-top-color: #343a40; }
    .tooltip.bs-tooltip-bottom .tooltip-arrow::before { border-bottom-color: #343a40; }
    .tooltip.bs-tooltip-start .tooltip-arrow::before { border-left-color: #343a40; }
    .tooltip.bs-tooltip-end .tooltip-arrow::before { border-right-color: #343a40; }

    /* Completion Section */
    #completionSection {
        display: none;
        padding: 3rem 1rem;
        background-color: #e9f5ff;
        border: 1px solid #bde0fe;
        border-radius: 1rem;
    }
     #completionSection h2 {
        text-align: center;
        color: #075985;
        margin-bottom: 1rem;
     }
    #completionSection p {
        text-align: center;
        color: #0369a1;
        margin-bottom: 1.5rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    .completion-options {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 2rem; /* Space between theme and format */
        margin-bottom: 2rem;
        align-items: flex-start;
    }
    .theme-selector-group, .format-selector-group {
        text-align: center;
        min-width: 200px; /* Ensure they don't get too squished */
    }
    .theme-selector-group label, .format-selector-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #0369a1;
        font-weight: 600;
    }
    #completionSection select {
        max-width: 250px;
        margin: 0 auto;
    }
    .download-buttons-container {
        text-align: center;
    }
    #downloadBtn { /* Style the main button */
        font-size: 1.1rem;
        padding: 0.8rem 2rem;
    }


    /* Footer - Keep styles for elements within the footer */
    /* The footer HTML structure itself comes from footer.php */
    .app-footer {
      background-color: #e9ecef;
      color: #6c757d;
      padding: 1.5rem 0;
      text-align: center;
      font-size: 0.9rem;
      margin-top: auto;
      border-top: 1px solid #dee2e6;
    }
    /* #saveStatus element removed, replaced by SweetAlert toasts */

    /* General Loading Overlay */
    #loadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        z-index: 1050; /* High z-index */
        display: none; /* Hidden by default */
        justify-content: center;
        align-items: center;
    }
    #loadingOverlay .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    /* AI Assistance Modal Specific Styles - START */
    #aiAssistanceModal .modal-xl {
        max-width: 900px;
    }
    #aiAssistanceModal .form-label.fw-medium {
        font-weight: 500 !important;
    }
    #aiAssistanceModal .ai-count-input {
        width: 70px;
    }
    #aiAssistanceModal .list-group-item-action h6 {
        font-weight: 600;
        color: #0d6efd;
    }
    #aiAssistanceModal .list-group-item-action p.small {
        line-height: 1.4;
        color: #495057;
    }
    #aiAssistanceModal .list-group-item-action:hover h6 {
        color: #0a58ca;
    }
    #aiAssistanceModal .list-group-item-action .bi-chevron-right {
        font-size: 1.2rem;
        color: #6c757d;
        transition: transform 0.2s ease-in-out;
    }
    #aiAssistanceModal .list-group-item-action:hover .bi-chevron-right {
        transform: translateX(3px);
        color: #0d6efd;
    }

    #aiAssistanceOutput {
        min-height: 150px;
        background-color: #f8f9fa;
        white-space: pre-wrap;
        word-wrap: break-word;
        max-height: 350px;
        overflow-y: auto;
        font-family: 'Inter', sans-serif;
        font-size: 0.9em;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
     #aiAssistanceOutput:not(:empty) {
        white-space: normal;
     }
     #aiAssistanceOutput h1, #aiAssistanceOutput h2, #aiAssistanceOutput h3, #aiAssistanceOutput h4 {
        font-size: 1.2em; margin-top: 0.8em; margin-bottom: 0.4em; font-weight: 600;
     }
     #aiAssistanceOutput p { margin-bottom: 0.5em; }
     #aiAssistanceOutput ul, #aiAssistanceOutput ol { margin-left: 1.5em; margin-bottom: 0.5em; padding-left: 1em; }
     #aiAssistanceOutput pre {
        white-space: pre-wrap;
        word-wrap: break-word;
        font-family: monospace;
        background-color: #e9ecef;
        padding: 0.75em;
        border-radius: 0.375rem;
        border: 1px solid #ced4da;
     }
    #aiAssistanceOutput .spinner-border {
        width: 2rem;
        height: 2rem;
    }
    .modal-footer .btn-group {
        width: 100%;
        justify-content: space-between;
    }
     .modal-footer .btn-group .btn {
        flex-grow: 1;
        margin: 0 0.25rem;
    }
    /* AI Assistance Modal Specific Styles - END */

    /* Inline AI Floating Button Style */
    #inlineAiFloatingButton {
        transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
    }
    #inlineAiFloatingButton.d-none {
        opacity: 0;
        transform: scale(0.8);
        pointer-events: none;
    }
     #inlineAiFloatingButton .dropdown-menu .dropdown-item {
        font-size: 0.9rem;
        padding-top: 0.4rem;
        padding-bottom: 0.4rem;
    }
    #inlineAiFloatingButton .dropdown-menu .dropdown-item i {
        margin-right: 0.5rem;
        width: 16px; /* Ensure consistent icon width */
    }
     .dropdown-submenu {
      position: relative;
    }
    .dropdown-submenu > .dropdown-menu {
      top: 0;
      left: 100%;
      margin-top: -1px;
      border-radius: 0 .25rem .25rem .25rem;
    }
    .dropdown-submenu:hover > .dropdown-menu {
      display: block;
    }
    .dropdown-submenu > a:after {
      display: block;
      content: " ";
      float: right;
      width: 0;
      height: 0;
      border-color: transparent;
      border-style: solid;
      border-width: 5px 0 5px 5px;
      border-left-color: #ccc;
      margin-top: 5px;
      margin-right: -10px;
    }
    .dropdown-submenu:hover > a:after {
      border-left-color: #fff;
    }
    .dropdown-submenu.pull-left {
      float: none;
    }
    .dropdown-submenu.pull-left > .dropdown-menu {
      left: -100%;
      margin-left: 10px;
      border-radius: .25rem 0 .25rem .25rem;
    }


    /* CSS for HTML report generation (used in temp container) */
    .report-render-container h1, .report-render-container h2, .report-render-container h3 { margin-top: 1em; margin-bottom: 0.5em; }
    .report-render-container ul, .report-render-container ol { margin-left: 20px; margin-bottom: 0.5em; }
    .report-render-container p { margin-bottom: 0.5em; }
    .report-render-container .ck-content { padding: 0 !important; margin: 0 !important; border: none !important; }
    .report-render-container .ck-content h2 { font-size: 1.2em; margin-top: 1em; margin-bottom: 0.5em; }
    .report-render-container .ck-content ul, .report-render-container .ck-content ol { margin-left: 20px; margin-bottom: 0.5em;}

  </style>

  <!-- Header -->
  <!-- This header HTML is part of the original HTML structure.
       If your header.php provides a similar structure, you should remove this
       and ensure the CSS classes target the structure from header.php -->
  <?php
  // Check if header.php provides the <header> tag. If so, remove this block.
  // If header.php only provides the <head> content, keep this block.
  /*
  ?>
  <header class="app-header">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="#" class="logo d-flex align-items-center">
             <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-book-half me-2" viewBox="0 0 16 16"><path d="M8.5 2.687c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388 1.175.885.653 1.498 1.534 1.498 2.585v8.207a.75.75 0 0 1-.75.75H8.5V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877V1.783z"/><path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m.5 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/></svg>
             eBook Planner
        </a>
        <h1>Assistente de Criação</h1>
        <div class="header-actions d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" id="templateDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-layout-text-sidebar-reverse" viewBox="0 0 16 16">
                        <path d="M12.5 3a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1h5zm0 3a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1h5zm.5 3.5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 .5-.5zm-.5 2.5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1h5z"/>
                        <path d="M16 2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2zM4 1v14H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h2zm1 0h9a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5V1z"/>
                    </svg>
                    Modelos
                </button>
                <ul class="dropdown-menu" id="templateDropdownMenu" aria-labelledby="templateDropdownMenuButton">
                    <!-- Modelos serão populados aqui pelo JS -->
                </ul>
            </div>
            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#aiAssistanceModal">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-stars" viewBox="0 0 16 16"><path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.58 5.48l-.386 1.161a.217.217 0 0 1-.412 0L3.404 5.48a1.73 1.73 0 0 0-1.097-1.097L1.148 4.002a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.404 2.11zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/></svg>
                 Assistência IA
            </button>
            <button type="button" id="saveProgressBtn" class="btn btn-sm btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1z"/></svg>
                 Salvar Progresso
            </button>
            <button type="button" id="resetPlanBtn" class="btn btn-sm btn-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                 Limpar Plano
            </button>
        </div>
    </div>
  </header>
  <?php
  */
  ?>


  <div id="apiKeyStatusContainer" class="container-fluid">
      <!-- Status da API Key será inserido aqui pelo JS -->
  </div>

  <!-- Main Content Area -->
  <main class="container">
    <div id="wizardContainer">
      <div id="progressIndicator" class="mt-3 mb-4"></div>
      <form id="wizardForm" class="wizard-card" novalidate> <!-- novalidate prevents browser default validation UI -->
        <div id="stepsContainer" aria-live="polite">
          <!-- Wizard steps will be injected here by JavaScript -->
        </div>
         <div id="validationErrorMessage" class="validation-error-message mt-3" style="display: none;"></div>
        <div class="d-flex justify-content-between navigation-buttons">
          <button type="button" id="prevBtn" class="btn btn-outline-secondary">Voltar</button>
          <button type="button" id="nextBtn" class="btn btn-primary">Próximo</button>
        </div>
      </form>
    </div>

    <!-- Completion Section (Initially Hidden) -->
    <div id="completionSection" class="wizard-card">
        <h2>🎉 Planejamento Concluído!</h2>
        <p>Seu plano detalhado para o eBook está pronto. Escolha um tema visual, o formato desejado e clique no botão abaixo para gerar e baixar o arquivo.</p>

        <div class="completion-options">
            <div class="theme-selector-group">
                <label for="reportThemeSelector">Escolha um tema visual:</label>
                <select id="reportThemeSelector" class="form-select">
                    <option value="default" selected>Padrão (Claro)</option>
                    <option value="dark">Escuro</option>
                    <option value="blueish">Azulado</option>
                </select>
            </div>

            <div class="format-selector-group">
                <label for="reportFormatSelector">Escolha o formato do arquivo:</label>
                <select id="reportFormatSelector" class="form-select">
                    <option value="html" selected>HTML (.html)</option>
                    <option value="markdown">Markdown (.md)</option>
                    <option value="text">Texto Simples (.txt)</option>
                    <option value="pdf">PDF Avançado (.pdf)</option>
                    <option value="pdf_simple">PDF Simples (Texto) (.pdf)</option>
                    <option value="json">JSON (.json)</option>
                </select>
            </div>
        </div>

        <div class="download-buttons-container">
            <button type="button" id="downloadBtn" class="btn btn-success">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download me-2" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                Baixar Plano do eBook
            </button>
        </div>
    </div>
  </main>

  <!-- AI Assistance Modal -->
  <div class="modal fade" id="aiAssistanceModal" tabindex="-1" aria-labelledby="aiAssistanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="aiAssistanceModalLabel">
            <i class="bi bi-stars me-2"></i>Assistência Inteligente IA
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-auto">
                <label for="aiSuggestionCountModal" class="form-label fw-medium mb-0 pt-1">Sugestões por Ação:</label>
            </div>
            <div class="col-md-2">
                <input type="number" id="aiSuggestionCountModal" class="form-control form-control-sm ai-count-input" value="3" min="1" max="10" aria-describedby="aiSuggestionCountHelp">
            </div>
            <div class="col-md">
                <small id="aiSuggestionCountHelp" class="form-text text-muted pt-1">Define o número de alternativas que a IA tentará gerar.</small>
            </div>
          </div>

          <p class="text-muted mb-3">Explore as ferramentas de IA abaixo para refinar e aprimorar o plano do seu eBook:</p>

          <div class="list-group mb-4">
            <button type="button" class="list-group-item list-group-item-action" data-action-type="generateIntroduction" aria-label="Gerar Rascunho de Introdução">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Rascunhar Introdução</h6>
                <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
              </div>
              <p class="mb-1 small text-muted">Gere um esboço inicial para a seção introdutória do seu eBook, com base no tema e público.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="reviewPlan" aria-label="Analisar Plano Completo">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><i class="bi bi-card-checklist me-2"></i>Analisar Plano Completo</h6>
                <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
              </div>
              <p class="mb-1 small text-muted">Receba feedback sobre a consistência, completude e alinhamento geral do seu plano.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="generateSummary" aria-label="Criar Lista de Capítulos">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><i class="bi bi-list-stars me-2"></i>Criar Lista de Capítulos</h6>
                <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
              </div>
              <p class="mb-1 small text-muted">Obtenha sugestões para os títulos dos capítulos principais do seu eBook.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="generateDetailedTOC" aria-label="Detalhar Sumário (TOC)">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><i class="bi bi-journal-richtext me-2"></i>Detalhar Sumário (TOC)</h6>
                <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
              </div>
              <p class="mb-1 small text-muted">Expanda seus capítulos com sugestões de sub-tópicos relevantes e organizados.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="analyzeTitleSubtitle" aria-label="Otimizar Título e Subtítulo">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><i class="bi bi-lightbulb me-2"></i>Otimizar Título e Subtítulo</h6>
                  <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
                </div>
                <p class="mb-1 small text-muted">Analise e receba sugestões para tornar seu título mais engajador e otimizado para SEO.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="generateMarketingDescription" aria-label="Criar Descrição de Marketing">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><i class="bi bi-blockquote-left me-2"></i>Criar Descrição de Marketing</h6>
                  <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
                </div>
                <p class="mb-1 small text-muted">Gere descrições curtas e persuasivas para divulgar seu eBook em diversas plataformas.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="suggestSeoKeywords" aria-label="Sugerir Palavras-Chave SEO">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><i class="bi bi-tags me-2"></i>Sugerir Palavras-Chave (SEO)</h6>
                  <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
                </div>
                <p class="mb-1 small text-muted">Descubra termos relevantes para melhorar a encontrabilidade do seu eBook.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="brainstormSupportContent" aria-label="Ideias para Conteúdo Extra">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><i class="bi bi-palette2 me-2"></i>Ideias para Conteúdo Extra</h6>
                  <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
                </div>
                <p class="mb-1 small text-muted">Brainstorm de temas para blogs, posts ou vídeos que complementem seu eBook.</p>
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-action-type="analyzePlannedTone" aria-label="Avaliar Tom de Voz">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><i class="bi bi-chat-quote me-2"></i>Avaliar Tom de Voz</h6>
                  <small class="text-muted"><i class="bi bi-chevron-right"></i></small>
                </div>
                <p class="mb-1 small text-muted">Analise a adequação do tom de voz planejado para seu público e tema.</p>
            </button>
          </div>
          <hr>
          <label for="aiAssistanceOutput" class="form-label fw-semibold mt-3">Resultado da IA:</label>
          <div id="aiAssistanceOutput" class="mt-1 p-3 border rounded bg-body-tertiary">
            O resultado da IA será exibido aqui.
          </div>
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button type="button" class="btn btn-success" id="aiApplyOutputBtn" style="display:none;" title="Aplicar sugestão ao campo correspondente no plano">
                    <i class="bi bi-check-circle-fill me-2"></i>Aplicar ao Plano
                </button>
                <button type="button" class="btn btn-warning" id="aiDiscardOutputBtn" style="display:none;" title="Limpar o resultado atual da IA">
                    <i class="bi bi-x-circle-fill me-2"></i>Descartar
                </button>
                <button type="button" class="btn btn-info" id="aiCopyOutputBtn" style="display:none;" title="Copiar o resultado da IA para a área de transferência">
                    <i class="bi bi-clipboard me-2"></i>Copiar Resultado
                </button>
            </div>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="aiCloseModalBtn">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
   <!-- This footer HTML is part of the original HTML structure.
       If your footer.php provides a similar structure, you should remove this
       and ensure the CSS classes target the structure from footer.php -->
  <?php
  // Check if footer.php provides the <footer> tag. If so, remove this block.
  // If footer.php only provides closing tags, keep this block.
  /*
  ?>
  <footer class="app-footer">
    <div class="container">
      &copy; <span id="currentYear"></span> eBook Planner. Todos os direitos reservados.
       <!-- saveStatus span foi removido, será substituído por toasts do SweetAlert2 -->
    </div>
  </footer>
  <?php
  */
  ?>


  <!-- General Loading Overlay -->
  <div id="loadingOverlay">
      <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Carregando...</span>
      </div>
  </div>

<?php
// Include Bootstrap JS Bundle AFTER your custom content
require_once __DIR__ . '/../src/templates/footer.php';
?>

<!-- Inject PHP variable into JavaScript -->
<script>
    const HAS_GEMINI_API_KEY_PHP = <?php echo $has_gemini_api_key_php; ?>;
    const BASE_URL_JS = '<?php echo e(BASE_URL); ?>'; // Assuming BASE_URL is defined in config
</script>

<!-- Custom JavaScript from original HTML - MODIFIED -->
  <script type="module">
    // Remove the client-side Google Generative AI SDK import
    // import { GoogleGenerativeAI, HarmCategory, HarmBlockThreshold } from "https://esm.run/@google/generative-ai";

    const STORAGE_KEY = 'ebookPlannerState_v2_3';
    const AUTO_SAVE_INTERVAL = 5000;
    const API_HANDLER_URL = BASE_URL_JS + 'src/api_handler.php'; // Point AI calls to your server handler

    const ckEditorFieldIds = [
        'step1_q0', // Persona
        'step4_q1', // Detalhe os sub-tópicos de cada capítulo principal.
        'step8_q1', // Descreva o estilo visual e a formatação desejada.
    ];
    let ckEditorInstances = {};

    // Keep the steps definition and template data in the JS for now
    const steps = [
        // ... (ALL STEPS DEFINITIONS AS PROVIDED PREVIOUSLY, UNCHANGED) ...
        // --- Step 1: Ideia Central & Propósito ---
        {
            title: "1. Ideia Central & Propósito",
            questions: [
                { id: "step0_q0", type: "text", label: "Qual é o tema principal do eBook?", description: "Defina o assunto central de forma clara e concisa.", placeholder: "Ex: Marketing de Conteúdo para Pequenas Empresas", tooltip: "Seja específico. Sobre o que exatamente é o livro?", required: true },
                { id: "step0_q1", type: "textarea", rows: 3, label: "Qual problema específico este eBook resolve?", description: "Todo bom eBook soluciona uma dor ou atende a um desejo do leitor.", placeholder: "Ex: Ajuda empreendedores a criar um calendário editorial eficaz sem gastar muito.", tooltip: "Pense no benefício direto e tangível para o leitor.", required: true, aiSuggestion: { type: 'problem', buttonText: 'Sugerir Problemas', countDefault: 3} },
                { id: "step0_q2", type: "textarea", rows: 3, label: "Por que você (ou sua marca) quer escrevê-lo?", description: "Sua motivação pessoal ou empresarial conecta com o leitor e define o tom.", placeholder: "Ex: Compartilhar nossa expertise em SEO e gerar leads qualificados; Paixão por ensinar culinária vegana.", tooltip: "Qual sua paixão, experiência ou objetivo comercial com este eBook?" }
            ]
        },
        // --- Step 2: Público-Alvo Detalhado ---
        {
            title: "2. Público-Alvo Detalhado",
            questions: [
                { id: "step1_q0", type: "textarea", rows: 4, label: "Quem é seu leitor ideal (persona)? (Use o editor abaixo)", description: "Descreva detalhadamente: idade, profissão, desafios, objetivos, onde busca informação.", placeholder: "Ex: Maria, 35 anos, dona de loja de artesanato...", tooltip: "Quanto mais detalhada a persona, mais direcionado será o conteúdo.", required: true, aiSuggestion: { type: 'persona', buttonText: 'Elaborar Persona'} },
                {
                    id: "step1_q1", type: "radio", label: "Qual o nível de conhecimento do leitor sobre o tema?", description: "Isso define a profundidade, a linguagem e os pré-requisitos.", tooltip: "Ajuste o vocabulário e a complexidade ao nível do seu público.", required: true,
                    options: [
                        { value: "iniciante", label: "Iniciante (precisa de conceitos básicos)" }, { value: "intermediario", label: "Intermediário (conhece o básico, busca táticas)" }, { value: "avancado", label: "Avançado (busca estratégias aprofundadas)" }, { value: "misto", label: "Misto (abrange vários níveis)" }
                    ]
                },
                { id: "step1_q2", type: "textarea", rows: 3, label: "Qual a principal dor ou necessidade que o eBook vai sanar?", description: "Conecte o problema central (Passo 1) diretamente com a realidade do público.", placeholder: "Ex: Perda de tempo com tarefas manuais...", tooltip: "O que realmente incomoda ou o que seu leitor mais deseja alcançar?", required: true, aiSuggestion: { type: 'painPoint', buttonText: 'Sugerir Dores', countDefault: 3} }
            ]
        },
        // --- Step 3: Objetivo Claro do Livro ---
        {
            title: "3. Objetivo Claro do Livro",
            questions: [
                { id: "step2_q0", type: "textarea", rows: 3, label: "O que o leitor será capaz de fazer ou saber após a leitura?", description: "Defina a transformação ou o resultado prático esperado.", placeholder: "Ex: Criar sua primeira campanha de anúncios...", tooltip: "Seja específico sobre a habilidade ou conhecimento adquirido.", required: true, aiSuggestion: { type: 'outcome', buttonText: 'Sugerir Resultados', countDefault: 3} },
                {
                    id: "step2_q1", type: "radio", label: "Qual é o seu objetivo principal com este eBook?", description: "Selecione a intenção primária.", tooltip: "Alinhe o conteúdo do eBook com seu objetivo estratégico.", required: true,
                    options: [
                         { value: "educar", label: "Educar o mercado / Compartilhar conhecimento" }, { value: "gerar_leads", label: "Gerar Leads (capturar contatos)" }, { value: "vender_produto", label: "Apoiar a venda de um produto/serviço" }, { value: "construir_autoridade", label: "Construir autoridade no nicho" }, { value: "inspirar", label: "Inspirar ou motivar" },
                    ],
                    otherOption: true
                }
            ]
        },
        // --- Step 4: Título e Subtítulo ---
         {
            title: "4. Título e Subtítulo Magnéticos",
            questions: [
                { id: "step3_q0", type: "text", label: "Qual será o título principal?", description: "Deve ser claro, atraente e indicar o benefício principal.", placeholder: "Ex: Descomplique suas Finanças Pessoais", tooltip: "Use palavras-chave relevantes e foque no resultado ou na curiosidade.", required: true, aiSuggestion: { type: 'titles', buttonText: 'Sugerir Títulos', countDefault: 3} },
                { id: "step3_q1", type: "text", label: "Qual o subtítulo descritivo?", description: "Complementa o título, especificando o conteúdo ou o público.", placeholder: "Ex: Um Guia Prático para Organizar seu Orçamento...", tooltip: "Detalhe o que o leitor encontrará ou para quem o livro é destinado.", required: false },
                { id: "step3_q2", type: "textarea", rows: 3, label: "Resuma a proposta do eBook em uma única frase (Elevator Pitch).", description: "Uma frase curta e impactante que comunica o valor essencial.", placeholder: "Ex: Este eBook ensina freelancers a dobrarem sua renda...", tooltip: "Pense em como você apresentaria o livro rapidamente.", required: true, aiSuggestion: { type: 'elevatorPitch', buttonText: 'Sugerir Pitch', countDefault: 3} }
            ]
        },
        // --- Step 5: Estrutura e Índice ---
        {
            title: "5. Estrutura e Índice Detalhado",
            questions: [
                { id: "step4_q0", type: "textarea", rows: 5, label: "Quais serão os principais capítulos ou seções?", description: "Liste os grandes blocos de conteúdo. Use uma linha por capítulo.", placeholder: "1. Introdução à IA Generativa\n2. Principais Ferramentas...", tooltip: "Pense na progressão lógica do aprendizado do leitor.", required: true, aiSuggestion: { type: 'chapters', buttonText: 'Sugerir Capítulos', countDefault: 5} },
                { id: "step4_q1", type: "textarea", rows: 8, label: "Detalhe os sub-tópicos de cada capítulo principal. (Use o editor abaixo)", description: "Divida cada capítulo em pontos menores e específicos. Use indentação ou numeração (a IA pode ajudar aqui se você colar a lista de capítulos).", placeholder: "Cap 2: Ferramentas\n  2.1 ChatGPT\n  2.2 Midjourney...", tooltip: "Isso formará o seu índice e guiará a escrita.", required: true },
                {
                    id: "step4_q2", type: "checkbox", label: "Quais elementos adicionais o eBook terá?", description: "Marque todas as opções aplicáveis.", tooltip: "Estruture o livro completo, do início ao fim.",
                    options: [
                        { value: "introducao", label: "Introdução Detalhada" }, { value: "conclusao", label: "Conclusão / Resumo Final" }, { value: "sobre_autor", label: "Sobre o Autor" }, { value: "glossario", label: "Glossário de Termos" }, { value: "recursos", label: "Lista de Recursos / Links Úteis" }, { value: "apendices", label: "Apêndices (material extra)" }, { value: "cta", label: "Chamada para Ação (CTA) específica" },
                    ],
                    otherOption: true,
                    aiSuggestion: { type: 'extraElements', buttonText: 'Sugerir Elementos', countDefault: 3 }
                }
            ]
        },
        // --- Step 6: Pesquisa ---
        {
            title: "6. Pesquisa e Fontes de Conteúdo",
            questions: [
                { id: "step5_q0", type: "textarea", rows: 4, label: "Quais fontes de informação você utilizará?", description: "Liste livros, artigos, estudos, entrevistas, sua própria experiência, etc.", placeholder: "Ex: Artigos científicos recentes do PubMed...", tooltip: "Garanta a credibilidade e profundidade do seu conteúdo.", required: true },
                {
                    id: "step5_q1", type: "radio", label: "Será necessário citar fontes específicas (autores, dados, pesquisas)?", description: "Planeje como fará as referências para evitar plágio e dar crédito.", tooltip: "Defina um padrão de citação, se necessário.", required: true,
                    options: [
                        { value: "nao", label: "Não, o conteúdo é majoritariamente baseado em experiência própria ou conhecimento geral." }, { value: "sim_informal", label: "Sim, mas de forma informal (ex: 'Segundo autor X...')" }, { value: "sim_formal", label: "Sim, com citações formais (notas de rodapé, bibliografia, etc.)" },
                    ]
                },
                { id: "step5_q2", type: "textarea", rows: 2, label: "Se sim, qual será o método de citação?", description: "(Opcional) Descreva brevemente o método se escolheu uma opção 'Sim' acima.", placeholder: "Ex: Usarei notas de rodapé estilo ABNT...", tooltip: "Seja consistente no método escolhido." },
            ]
        },
        // --- Step 7: Tom de Voz ---
        {
            title: "7. Tom de Voz e Estilo de Redação",
            questions: [
                {
                    id: "step6_q0", type: "select", label: "Qual será o tom de voz predominante?", description: "Selecione o tom que melhor se conecta com seu público e objetivo.", tooltip: "O tom deve ser consistente ao longo do eBook.", required: true,
                    options: [
                        { value: "", label: "-- Selecione um Tom --" }, { value: "formal", label: "Formal / Acadêmico" }, { value: "profissional", label: "Profissional / Corporativo" }, { value: "informal", label: "Informal / Conversacional" }, { value: "didatico", label: "Didático / Educacional" }, { value: "inspirador", label: "Inspirador / Motivacional" }, { value: "divertido", label: "Divertido / Humorístico" }, { value: "tecnico", label: "Técnico / Especializado" },
                    ]
                },
                { id: "step6_q1", type: "textarea", rows: 3, label: "Descreva brevemente o estilo desejado.", description: "Adicione nuances ao tom selecionado.", placeholder: "Ex: Conversa amigável, mas direta ao ponto...", tooltip: "Pense em adjetivos que definam a escrita.", aiSuggestion: { type: 'writingStyle', buttonText: 'Sugerir Estilo', countDefault: 3} },
                { id: "step6_q2", type: "textarea", rows: 4, label: "Como você garantirá clareza e coesão entre os capítulos?", description: "Pense em elementos de ligação, resumos, e fluxo lógico.", placeholder: "Ex: Usar introduções e conclusões curtas...", tooltip: "Facilite a leitura e a compreensão do conteúdo como um todo." }
            ]
        },
        // --- Step 8: Revisão ---
        {
            title: "8. Processo de Revisão e Edição",
            questions: [
                {
                    id: "step7_q0", type: "checkbox", label: "Quais etapas de revisão você planeja realizar?", description: "Marque todas as etapas previstas. Recomenda-se múltiplas revisões.", tooltip: "Uma boa revisão é crucial para a qualidade final.", required: true,
                    options: [
                        { value: "auto_conteudo", label: "Auto-revisão focada em Conteúdo e Estrutura" }, { value: "auto_gramatica", label: "Auto-revisão focada em Gramática e Ortografia" }, { value: "leitura_voz_alta", label: "Leitura em voz alta (pega erros de fluidez)" }, { value: "revisor_amigo", label: "Revisão por colega ou amigo (leitor beta)" }, { value: "revisor_profissional", label: "Contratação de Revisor Profissional" }, { value: "editor_profissional", label: "Contratação de Editor Profissional (mais profundo que revisão)" },
                    ],
                    otherOption: true
                },
                {
                    id: "step7_q1", type: "checkbox", label: "Quais ferramentas de apoio pretende utilizar?", description: "Marque as ferramentas que auxiliarão no processo.", tooltip: "Ferramentas podem otimizar a revisão, mas não substituem a leitura atenta.",
                    options: [
                        { value: "corretor_word", label: "Corretor Ortográfico/Gramatical (Word, Docs, etc.)" }, { value: "grammarly", label: "Ferramentas Avançadas (Grammarly, LanguageTool, etc.)" }, { value: "plagio", label: "Verificador de Plágio" }, { value: "dicionario", label: "Dicionários (Sinônimos, Significados)" }, { value: "manual_estilo", label: "Manual de Estilo (próprio ou de mercado)" },
                    ],
                    otherOption: true
                }
            ]
        },
        // --- Step 9: Design ---
        {
            title: "9. Design, Formatação e Formato Final",
            questions: [
                {
                    id: "step8_q0", type: "checkbox", label: "Quais serão os formatos finais de entrega?", description: "Selecione todos os formatos que serão disponibilizados.", tooltip: "Considere onde e como seus leitores preferem ler. PDF é universal.", required: true,
                    options: [
                        { value: "pdf", label: "PDF (Layout fixo, ideal para impressão e visualização universal)" }, { value: "epub", label: "EPUB (Layout fluido, padrão para e-readers/apps, exceto Kindle)" }, { value: "mobi", label: "MOBI / AZW3 (Layout fluido, formato para Kindle - Amazon)" }, { value: "web", label: "Versão Online / HTML (Acessível via navegador)" }
                    ],
                    otherOption: true
                },
                { id: "step8_q1", type: "textarea", rows: 6, label: "Descreva o estilo visual e a formatação desejada. (Use o editor abaixo)", description: "Pense em layout, fontes, cores, uso de imagens, gráficos, etc.", placeholder: "Ex: Design moderno e limpo, com cores da minha marca...", tooltip: "O design impacta a experiência de leitura e a percepção de valor." },
                {
                    id: "step8_q2", type: "radio", label: "Quem fará o design e a formatação final?", description: "Seja realista sobre suas habilidades, tempo e orçamento.", tooltip: "Um design profissional pode fazer a diferença.", required: true,
                    options: [
                         { value: "diy_basico", label: "Eu mesmo (DIY básico - Word/Docs)" }, { value: "diy_template", label: "Eu mesmo (Usando template - Canva, InDesign Template, etc.)" }, { value: "freelancer", label: "Contratar Freelancer (Designer/Diagramador)" }, { value: "agencia", label: "Contratar Agência Especializada" },
                    ]
                }
            ]
        },
        // --- Step 10: Capa ---
        {
            title: "10. Criação da Capa Impactante",
            questions: [
                { id: "step9_q0", type: "textarea", rows: 4, label: "Descreva a ideia visual para a capa.", description: "Pense em cores, imagens, fontes e o sentimento que deseja transmitir.", placeholder: "Ex: Fundo azul escuro, título grande...", tooltip: "A capa é a primeira impressão – deve ser atraente, legível em miniatura e profissional.", aiSuggestion: { type: 'coverConcept', buttonText: 'Sugerir Conceitos', countDefault: 2} },
                {
                    id: "step9_q1", type: "radio", label: "Quem criará a capa?", description: "Considere a importância da capa para a atratividade do eBook.", tooltip: "Investir em uma boa capa geralmente vale a pena.", required: true,
                    options: [
                        { value: "diy_canva", label: "Eu mesmo (DIY - Canva ou similar)" }, { value: "freelancer_design", label: "Contratar Freelancer (Designer Gráfico)" }, { value: "freelancer_capista", label: "Contratar Capista Especializado" }, { value: "agencia", label: "Contratar Agência" }, { value: "designer_interno", label: "Designer da minha equipe/empresa" }
                    ]
                }
            ]
        },
        // --- Step 11: Divulgação ---
        {
            title: "11. Estratégia de Divulgação e Lançamento",
            questions: [
                {
                    id: "step10_q0", type: "radio", label: "Qual será o modelo principal de distribuição?", description: "Como o leitor terá acesso ao eBook?", tooltip: "Defina como seu eBook chegará ao público.", required: true,
                    options: [
                         { value: "gratuito_site", label: "Gratuito (Download direto no site/blog)" }, { value: "isca_digital", label: "Isca Digital (Gratuito em troca de email/contato)" }, { value: "pago_amazon", label: "Pago (Venda na Amazon KDP)" }, { value: "pago_hotmart", label: "Pago (Venda em plataformas - Hotmart, Eduzz, etc.)" }, { value: "pago_proprio", label: "Pago (Venda direta no próprio site)" },
                    ],
                    otherOption: true
                },
                {
                    id: "step10_q1", type: "checkbox", label: "Quais canais de marketing e divulgação você planeja usar?", description: "Marque todas as estratégias que pretende implementar.", tooltip: "Um bom eBook merece uma boa divulgação. Combine canais!", required: true,
                    options: [
                        { value: "email_mkt", label: "Email Marketing (para lista existente)" }, { value: "social_organico", label: "Redes Sociais (Posts orgânicos)" }, { value: "social_ads", label: "Anúncios Pagos (Facebook/Instagram Ads, Google Ads)" }, { value: "blog_seo", label: "Conteúdo de Blog / SEO" }, { value: "parcerias", label: "Parcerias / Influenciadores" }, { value: "webinar", label: "Webinar / Evento de Lançamento" }, { value: "assessoria", label: "Assessoria de Imprensa / Mídia" }, { value: "grupos", label: "Grupos / Comunidades Online" },
                    ],
                    otherOption: true,
                    aiSuggestion: { type: 'marketingChannels', buttonText: 'Sugerir Canais', countDefault: 5}
                },
                { id: "step10_q2", type: "textarea", rows: 3, label: "Detalhe a principal ação de lançamento.", description: "Qual será o 'grande evento' ou foco inicial da divulgação?", placeholder: "Ex: Semana de lançamento com lives diárias...", tooltip: "Tenha um plano claro para o momento do lançamento.", aiSuggestion: { type: 'launchAction', buttonText: 'Sugerir Ação', countDefault: 2} }
            ]
        },
        // --- Step 12: Pós-Lançamento ---
        {
            title: "12. Pós-Lançamento e Atualizações",
            questions: [
                {
                    id: "step11_q0", type: "radio", label: "Você planeja revisar ou atualizar o conteúdo no futuro?", description: "Um eBook pode precisar de atualizações, especialmente em temas dinâmicos.", tooltip: "Manter o conteúdo relevante aumenta sua longevidade e valor.",
                    options: [
                         { value: "sim_regular", label: "Sim, regularmente (ex: anualmente, semestralmente)" }, { value: "sim_conforme", label: "Sim, conforme necessário (grandes mudanças no tema)" }, { value: "talvez", label: "Talvez, dependendo do feedback e desempenho" }, { value: "nao", label: "Não, o conteúdo é atemporal / não há planos de atualização" }
                    ]
                },
                { id: "step11_q1", type: "text", label: "Se sim, qual a frequência estimada de atualização?", description:"(Opcional) Especifique o intervalo se escolheu 'Sim, regularmente'.", placeholder:"Ex: Anualmente; A cada 6 meses", tooltip:"Ajuda a planejar a manutenção."},
                {
                    id: "step11_q2", type: "checkbox", label: "Como você coletará feedback dos leitores?", description: "Marque as formas de ouvir seu público.", tooltip: "O feedback é valioso para melhorias contínuas e novas ideias.",
                    options: [
                         { value: "form_ebook", label: "Link para Formulário de Feedback dentro do eBook" }, { value: "email_pos", label: "Email Pós-Download/Compra solicitando feedback" }, { value: "comentarios_site", label: "Monitorar Comentários no site/blog" }, { value: "comentarios_venda", label: "Monitorar Avaliações/Comentários na plataforma de venda" }, { value: "redes_sociais", label: "Monitorar Menções em Redes Sociais" }, { value: "pesquisa_lista", label: "Enviar Pesquisa para a lista de emails" },
                    ],
                    otherOption: true
                }
            ]
        }
    ];
    const ebookTemplates = {
        "guias_educacionais": {
            name: "Guias Educacionais e Tutoriais",
            subcategories: {
                "tecnologia_software": {
                    name: "Tecnologia e Software",
                    templates: {
                        "guia_saas": {
                            name: "Guia Completo de [Software SaaS]",
                            data: {
                                "step0_q0": "Guia Definitivo do [Nome do Software SaaS] para Iniciantes e Usuários Intermediários",
                                "step0_q1": "Ajudar novos e existentes usuários a dominar as funcionalidades essenciais do [Nome do Software SaaS], otimizar seu fluxo de trabalho e extrair o máximo valor da ferramenta.",
                                "step0_q2": "Posicionar nossa marca como especialista em [Área do Software], educar nossa base de usuários e atrair novos clientes interessados em produtividade com [Nome do Software SaaS].",
                                "step1_q0": "<p><strong>Persona Primária:</strong> Joana, 32 anos, gerente de projetos em uma PME. Precisa implementar e treinar sua equipe no [Nome do Software SaaS] para melhorar a colaboração e o acompanhamento de tarefas. Desafios: Tempo limitado, equipe com diferentes níveis de familiaridade tecnológica. Objetivos: Aumentar a eficiência da equipe, ter relatórios claros de progresso. Busca informação em blogs de produtividade, tutoriais em vídeo e fóruns do software.</p><p><strong>Persona Secundária:</strong> Carlos, 25 anos, freelancer de marketing digital. Quer usar o [Nome do Software SaaS] para gerenciar múltiplos clientes e projetos. Desafios: Organizar demandas, manter clientes atualizados. Objetivos: Escalar seus serviços, parecer mais profissional. Busca tutoriais rápidos e dicas avançadas.</p>",
                                "step1_q1": "misto",
                                "step1_q2": "Dificuldade em entender todas as funcionalidades do [Nome do Software SaaS] e como aplicá-las eficientemente no dia a dia, resultando em subutilização da ferramenta ou processos manuais demorados.",
                                "step2_q0": "O leitor será capaz de configurar o [Nome do Software SaaS] do zero, gerenciar projetos/tarefas/recursos [dependendo do core do software], colaborar efetivamente com sua equipe, gerar relatórios básicos e conhecer as melhores práticas para [principal benefício do software].",
                                "step2_q1": "educar",
                                "step3_q0": "Desvendando o [Nome do Software SaaS]: Seu Guia Prático para Máxima Produtividade",
                                "step3_q1": "Do Básico ao Avançado: Domine Ferramentas, Fluxos e Segredos para Transformar seu Trabalho",
                                "step3_q2": "Este eBook é o seu passaporte para dominar o [Nome do Software SaaS], transformando-o de uma simples ferramenta em um poderoso aliado da sua produtividade e organização.",
                                "step4_q0": "Introdução: Por que o [Nome do Software SaaS] e o que esperar deste guia?\nCapítulo 1: Primeiros Passos – Configuração e Interface\nCapítulo 2: Dominando [Funcionalidade Core 1 – Ex: Gerenciamento de Tarefas]\nCapítulo 3: Explorando [Funcionalidade Core 2 – Ex: Colaboração em Equipe]\nCapítulo 4: [Funcionalidade Core 3 – Ex: Relatórios e Análises]\nCapítulo 5: Dicas Pro e Truques Escondidos para Usuários Avançados\nCapítulo 6: Integrando o [Nome do Software SaaS] com Outras Ferramentas\nConclusão: Próximos Passos e Mantendo-se Atualizado",
                                "step4_q1": "<h2>Capítulo 1: Primeiros Passos – Configuração e Interface</h2><ul><li>Criando sua conta e entendendo os planos</li><li>Visão geral do dashboard principal</li><li>Personalizando suas preferências e notificações</li><li>Convidando membros da equipe e gerenciando acessos</li></ul><p>&nbsp;</p><h2>Capítulo 2: Dominando [Funcionalidade Core 1]</h2><ul><li>Criando e atribuindo [itens da funcionalidade]</li><li>Definindo prazos, prioridades e dependências</li><li>Utilizando visualizações (Kanban, Lista, Calendário)</li><li>Templates de [itens da funcionalidade] para agilizar</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_glossario": "on",
                                "step6_q0": "didatico",
                                "step6_q1": "Claro, objetivo, com exemplos práticos e screenshots (a serem adicionados no design). Linguagem acessível, mas precisa.",
                                "step8_q0_pdf": "on", "step8_q0_web": "on",
                                "step10_q0": "gratuito_site",
                                "step10_q1_email_mkt": "on", "step10_q1_blog_seo": "on", "step10_q1_social_organico": "on"
                            }
                        }
                    }
                },
                "desenvolvimento_pessoal": {
                    name: "Desenvolvimento Pessoal",
                    templates: {
                        "gestao_tempo": {
                            name: "Dominando a Arte da Gestão do Tempo",
                            data: {
                                "step0_q0": "Gestão Eficaz do Tempo para Profissionais Ocupados",
                                "step0_q1": "Ajudar profissionais a superar a procrastinação, organizar suas prioridades e encontrar mais tempo para o que realmente importa, tanto na vida profissional quanto pessoal.",
                                "step0_q2": "Compartilhar técnicas comprovadas de gestão do tempo que transformaram minha própria produtividade e bem-estar, inspirando outros a alcançar o mesmo.",
                                "step1_q0": "<p><strong>Persona:</strong> Ana, 40 anos, empreendedora e mãe de dois filhos. Sente-se constantemente sobrecarregada, com dificuldade de equilibrar as demandas do negócio e da família. Desafios: Interrupções constantes, dificuldade em dizer não, cansaço. Objetivos: Ter mais controle sobre seu dia, reduzir o estresse, ter tempo para si mesma. Busca informação em livros de autoajuda, podcasts sobre produtividade e artigos online.</p>",
                                "step1_q1": "misto",
                                "step1_q2": "Sentimento de estar sempre 'correndo atrás', sem conseguir finalizar tarefas importantes ou ter tempo para atividades prazerosas e de autocuidado, levando ao estresse e burnout.",
                                "step2_q0": "O leitor será capaz de identificar seus 'ladrões de tempo', aplicar técnicas como Matriz de Eisenhower e Pomodoro, planejar sua semana eficientemente, delegar tarefas e estabelecer limites saudáveis para proteger seu tempo.",
                                "step2_q1": "inspirar",
                                "step3_q0": "Tempo Rei: Conquiste Sua Agenda e Transforme Sua Vida",
                                "step3_q1": "Um Guia Prático com Técnicas Comprovadas para Você Parar de Correr e Começar a Viver",
                                "step3_q2": "Este eBook oferece um arsenal de estratégias práticas para você retomar o controle do seu tempo, aumentar sua produtividade e, o mais importante, viver uma vida com mais propósito e menos estresse.",
                                "step4_q0": "Introdução: A Ilusão da Falta de Tempo\nCapítulo 1: Autoconhecimento – Entendendo Seu Uso do Tempo Atual\nCapítulo 2: Definindo Prioridades Claras – O que Realmente Importa?\nCapítulo 3: Ferramentas e Técnicas de Planejamento Semanal e Diário\nCapítulo 4: Vencendo a Procrastinação e Mantendo o Foco\nCapítulo 5: A Arte de Dizer Não e Delegar Tarefas\nCapítulo 6: Gerenciando Energia, Não Apenas Tempo\nCapítulo 7: Criando Hábitos Sustentáveis de Gestão do Tempo\nConclusão: Seu Novo Relacionamento com o Tempo",
                                "step4_q1": "<h2>Capítulo 1: Autoconhecimento</h2><ul><li>Registrando suas atividades (Time Log)</li><li>Identificando seus maiores desperdiçadores de tempo</li><li>Entendendo seus picos de produtividade (cronotipo)</li></ul><p>&nbsp;</p><h2>Capítulo 2: Definindo Prioridades</h2><ul><li>A Matriz de Eisenhower (Urgente vs. Importante)</li><li>Técnica MoSCoW (Must have, Should have, Could have, Won't have)</li><li>Alinhando suas tarefas com seus objetivos de longo prazo</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_cta": "on",
                                "step6_q0": "inspirador",
                                "step6_q1": "Empático, motivador, com histórias reais (anonimizadas ou do autor) e exercícios práticos. Linguagem positiva e encorajadora.",
                                "step8_q0_pdf": "on", "step8_q0_epub": "on",
                                "step10_q0": "isca_digital",
                                "step10_q1_social_organico": "on", "step10_q1_webinar": "on", "step10_q1_parcerias": "on"
                            }
                        }
                    }
                }
            }
        },
        "marketing_negocios": {
            name: "Marketing e Negócios",
            subcategories: {
                "geracao_leads": {
                    name: "Geração de Leads e Vendas",
                    templates: {
                        "email_mkt_avancado": {
                            name: "Estratégias Avançadas de Email Marketing",
                            data: {
                                "step0_q0": "Email Marketing Avançado para Máxima Conversão",
                                "step0_q1": "Capacitar profissionais de marketing e donos de negócios a criar campanhas de email marketing altamente eficazes que nutrem leads, aumentam o engajamento e geram mais vendas.",
                                "step0_q2": "Consolidar nossa agência como referência em automação de marketing e funis de venda, gerando leads qualificados para nossos serviços de consultoria.",
                                "step1_q0": "<p><strong>Persona:</strong> Ricardo, 45 anos, diretor de marketing de uma empresa de médio porte no setor B2B. Já utiliza email marketing básico, mas sente que suas campanhas não estão performando bem. Desafios: Baixas taxas de abertura e clique, dificuldade em segmentar a base, não sabe como criar fluxos de nutrição eficazes. Objetivos: Aumentar o ROI do email marketing, gerar mais SQLs (Sales Qualified Leads). Busca informação em blogs de marketing, webinars de ferramentas e cases de sucesso.</p>",
                                "step1_q1": "intermediario",
                                "step1_q2": "Dificuldade em transformar uma lista de emails em clientes pagantes, com campanhas genéricas que não engajam e não levam o lead pela jornada de compra de forma eficiente.",
                                "step2_q0": "O leitor será capaz de planejar funis de email marketing, segmentar sua base de forma inteligente, escrever copy persuasiva para emails, criar fluxos de automação para nutrição e vendas, analisar métricas chave e otimizar suas campanhas continuamente.",
                                "step2_q1": "gerar_leads",
                                "step3_q0": "Email Marketing que Converte: Do Lead ao Cliente Fiel",
                                "step3_q1": "O Guia Definitivo com Estratégias, Automações e Copywriting para Multiplicar Suas Vendas",
                                "step3_q2": "Transforme seu email marketing em uma máquina de vendas com este guia completo, repleto de táticas avançadas e exemplos práticos para engajar e converter leads.",
                                "step4_q0": "Introdução: O Poder Subestimado do Email Marketing Moderno\nCapítulo 1: Planejamento Estratégico: Funis e Jornada do Cliente\nCapítulo 2: Construção e Higienização de Listas de Email de Qualidade\nCapítulo 3: Segmentação Avançada: Entregando a Mensagem Certa para a Pessoa Certa\nCapítulo 4: Copywriting para Emails: Escrevendo Assuntos e Conteúdos Irresistíveis\nCapítulo 5: Design e Layout de Emails que Performam\nCapítulo 6: Automação de Marketing: Criando Fluxos Inteligentes de Nutrição e Venda\nCapítulo 7: Testes A/B e Otimização Contínua de Campanhas\nCapítulo 8: Métricas Essenciais: Analisando Resultados e Calculando ROI\nConclusão: O Futuro do Email Marketing e Seus Próximos Passos",
                                "step4_q1": "<h2>Capítulo 6: Automação de Marketing</h2><ul><li>Tipos de fluxos de automação (boas-vindas, abandono de carrinho, nutrição de leads, reengajamento)</li><li>Gatilhos e condições para iniciar e mover leads nos fluxos</li><li>Personalização dinâmica de conteúdo em emails automatizados</li><li>Ferramentas populares de automação de email marketing</li></ul><p>&nbsp;</p><h2>Capítulo 4: Copywriting para Emails</h2><ul><li>Estrutura de um email persuasivo (AIDA, PAS)</li><li>Técnicas para escrever assuntos que aumentam a taxa de abertura</li><li>Uso de gatilhos mentais e storytelling em emails</li><li>CTAs (Call to Actions) eficazes para diferentes objetivos</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_cta": "on",
                                "step6_q0": "profissional",
                                "step6_q1": "Direto ao ponto, focado em resultados, com jargões de marketing explicados. Exemplos práticos e estudos de caso (hipotéticos ou reais anonimizados).",
                                "step8_q0_pdf": "on",
                                "step8_q2": "diy_template",
                                "step10_q0": "isca_digital",
                                "step10_q1_email_mkt": "on", "step10_q1_social_ads": "on", "step10_q1_blog_seo": "on", "step10_q1_webinar": "on",
                                "step11_q0": "sim_conforme"
                            }
                        }
                    }
                },
                "branding_conteudo": {
                    name: "Branding e Conteúdo",
                    templates: {
                         "marca_pessoal_forte": {
                            name: "Construindo uma Marca Pessoal Forte Online",
                            data: {
                                "step0_q0": "Construção e Fortalecimento de Marca Pessoal no Ambiente Digital",
                                "step0_q1": "Guiar profissionais e empreendedores no processo de definir, construir e comunicar uma marca pessoal autêntica e impactante online, que gere autoridade e oportunidades.",
                                "step0_q2": "Compartilhar minha jornada e aprendizados na construção da minha própria marca pessoal, ajudando outros a evitar erros comuns e acelerar seu crescimento.",
                                "step1_q0": "<p><strong>Persona:</strong> Laura, 28 anos, consultora de RH recém-formada. Quer se destacar no mercado e atrair clientes para seus serviços de consultoria. Desafios: Não sabe por onde começar, medo de se expor, dificuldade em definir seu nicho. Objetivos: Ser reconhecida como especialista, conseguir seus primeiros clientes, construir uma rede de contatos. Busca inspiração em perfis de sucesso no LinkedIn, blogs sobre carreira e marketing personal.</p>",
                                "step1_q1": "iniciante",
                                "step1_q2": "Sentir-se 'invisível' no mercado digital, com dificuldade de comunicar seu valor único e atrair as oportunidades certas, resultando em pouca diferenciação e crescimento lento.",
                                "step2_q0": "O leitor será capaz de identificar seus talentos e paixões, definir seu nicho e proposta de valor, criar uma identidade visual e verbal consistente, escolher as plataformas digitais certas, produzir conteúdo relevante e construir uma rede de contatos estratégica.",
                                "step2_q1": "construir_autoridade",
                                "step3_q0": "Marca Pessoal Imparável: De Anônimo a Referência no Seu Nicho",
                                "step3_q1": "O Guia Passo a Passo para Construir Sua Autoridade Online, Atrair Oportunidades e Deixar Sua Marca no Mundo",
                                "step3_q2": "Descubra como transformar sua paixão e conhecimento em uma marca pessoal magnética que abre portas e te posiciona como líder no seu mercado com este guia prático.",
                                "step4_q0": "Introdução: A Era da Marca Pessoal – Por que Você Precisa de Uma?\nCapítulo 1: Autoconhecimento Profundo: A Base da Sua Marca\nCapítulo 2: Definindo Seu Nicho e Proposta Única de Valor (PUV)\nCapítulo 3: Identidade Visual e Verbal: Comunicando Quem Você É\nCapítulo 4: Escolhendo Suas Plataformas Digitais Estratégicas (LinkedIn, Instagram, Blog, etc.)\nCapítulo 5: Marketing de Conteúdo para Marca Pessoal: Criando Valor e Engajamento\nCapítulo 6: Networking Estratégico Online e Offline\nCapítulo 7: Monetizando Sua Marca Pessoal: Gerando Renda com Sua Expertise\nCapítulo 8: Lidando com Críticas e Mantendo a Autenticidade\nConclusão: Sua Marca Pessoal em Evolução Contínua",
                                "step4_q1": "<h2>Capítulo 1: Autoconhecimento Profundo</h2><ul><li>Identificando seus talentos, paixões e valores</li><li>Análise SWOT pessoal (Forças, Fraquezas, Oportunidades, Ameaças)</li><li>Descobrindo seu 'porquê' (Golden Circle de Simon Sinek)</li><li>Coletando feedback sobre sua imagem atual</li></ul><p>&nbsp;</p><h2>Capítulo 5: Marketing de Conteúdo para Marca Pessoal</h2><ul><li>Formatos de conteúdo ideais para cada plataforma</li><li>Pilares de conteúdo e calendário editorial</li><li>Técnicas de storytelling para conectar com a audiência</li><li>Como promover seu conteúdo e aumentar o alcance</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_sobre_autor": "on",
                                "step6_q0": "informal",
                                "step6_q1": "Conversacional, inspirador, com exercícios práticos e prompts para reflexão. Histórias de sucesso (reais ou fictícias) para ilustrar conceitos.",
                                "step8_q0_pdf": "on", "step8_q0_epub": "on", "step8_q0_mobi": "on",
                                "step10_q0": "pago_hotmart",
                                "step10_q1_social_organico": "on", "step10_q1_social_ads": "on", "step10_q1_parcerias": "on", "step10_q1_webinar": "on",
                                "step11_q0": "sim_regular", "step11_q1": "Anualmente"
                            }
                        }
                    }
                }
            }
        },
        "culinaria_estilovida": {
            name: "Culinária e Estilo de Vida",
            // No subcategories for this example, templates directly under category
            templates: {
                "receitas_veganas_rapidas": {
                    name: "Receitas Veganas Rápidas para o Dia a Dia",
                     data: {
                        "step0_q0": "Culinária Vegana Prática e Saborosa para Iniciantes",
                        "step0_q1": "Mostrar que a culinária vegana pode ser deliciosa, acessível e rápida, desmistificando a ideia de que é complicada ou sem graça, e ajudando pessoas a incluir mais refeições à base de plantas em sua rotina.",
                        "step0_q2": "Compartilhar minha paixão pela culinária vegana, tornando-a mais acessível e inspirando um estilo de vida mais saudável e sustentável, além de construir uma comunidade online em torno do tema.",
                        "step1_q0": "<p><strong>Persona:</strong> Mariana, 29 anos, profissional de marketing que trabalha em home office. Quer adotar uma alimentação mais saudável e reduzir o consumo de carne, mas tem pouco tempo para cozinhar e se sente intimidada por receitas complexas. Desafios: Falta de tempo, pouca familiaridade com ingredientes veganos, medo de comidas sem sabor. Objetivos: Comer de forma mais saudável, aprender receitas veganas fáceis, sentir-se mais energizada. Busca inspiração no Instagram, Pinterest e blogs de culinária.</p>",
                        "step1_q1": "iniciante",
                        "step1_q2": "Dificuldade em encontrar receitas veganas que sejam ao mesmo tempo rápidas, fáceis de preparar com ingredientes acessíveis e verdadeiramente saborosas, levando à desistência ou frustração.",
                        "step2_q0": "O leitor será capaz de preparar mais de [Número] receitas veganas deliciosas para café da manhã, almoço, jantar e lanches em menos de 30 minutos cada, entender substituições básicas de ingredientes e montar uma lista de compras vegana essencial.",
                        "step2_q1": "educar",
                        "step3_q0": "Vegano Express: Sabor e Praticidade na Sua Cozinha em Minutos",
                        "step3_q1": "[Número]+ Receitas Deliciosas e Rápidas para Descomplicar Sua Alimentação à Base de Plantas",
                        "step3_q2": "Descubra como a culinária vegana pode ser incrivelmente fácil, rápida e cheia de sabor com este eBook repleto de receitas testadas e aprovadas para o seu dia a dia corrido.",
                        "step4_q0": "Introdução: Bem-vindo ao Mundo Delicioso da Culinária Vegana Rápida!\nCapítulo 1: Despensa Vegana Inteligente: Ingredientes Essenciais e Onde Encontrá-los\nCapítulo 2: Café da Manhã Energizante em Minutos (Ex: Smoothies, Mingaus, Tostas)\nCapítulo 3: Almoços Leves e Nutritivos (Ex: Saladas Completas, Wraps, Sopas Rápidas)\nCapítulo 4: Jantares Saborosos e Práticos (Ex: Massas de Panela Única, Curries Express, Mexidos)\nCapítulo 5: Lanches e Belisquetes Saudáveis (Ex: Pastinhas, Bolachas Caseiras, Frutas Turbinadas)\nCapítulo 6: Dicas Extras: Congelamento, Reaproveitamento e Planejamento Semanal\nConclusão: Sua Jornada Vegana Deliciosa Continua!",
                        "step4_q1": "<h2>Capítulo 2: Café da Manhã Energizante em Minutos</h2><ul><li>Smoothie Verde Detox Power</li><li>Overnight Oats Cremoso com Frutas Vermelhas</li><li>Tosta de Abacate Turbinada com Grão de Bico Crocante</li><li>Panqueca Vegana de Banana (3 ingredientes)</li></ul><p>&nbsp;</p><h2>Capítulo 4: Jantares Saborosos e Práticos</h2><ul><li>Macarrão Cremoso de Abobrinha com Molho de Tomate Caseiro Rápido</li><li>Curry Indiano de Lentilha Vermelha (Pronto em 20 minutos)</li><li>Tacos Veganos Divertidos com Feijão Preto e Guacamole</li><li>Arroz Frito Asiático com Tofu e Legumes</li></ul>",
                        "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_glossario": "on", "step4_q2_sobre_autor": "on",
                        "step6_q0": "informal",
                        "step6_q1": "Amigável, encorajador, como uma conversa com um amigo que adora cozinhar. Instruções claras e simples. Fotos vibrantes (a serem adicionadas no design).",
                        "step8_q0_pdf": "on", "step8_q0_epub": "on",
                        "step8_q1": "<p>Design limpo, moderno e apetitoso. Uso de cores vibrantes e fontes legíveis. Muitas fotos de alta qualidade das receitas. Layout que facilite a leitura rápida dos ingredientes e modo de preparo, talvez com ícones para tempo de preparo e dificuldade.</p>",
                        "step8_q2": "freelancer",
                        "step9_q0": "Capa com uma foto bem colorida e apetitosa de uma das receitas principais. Título grande e chamativo. Nome do autor em destaque. Cores alegres e que remetam à alimentação saudável (verdes, laranjas, amarelos).",
                        "step9_q1": "freelancer_design",
                        "step10_q0": "pago_amazon",
                        "step10_q1_social_organico": "on", "step10_q1_social_ads": "on", "step10_q1_blog_seo": "on", "step10_q1_parcerias": "on",
                        "step11_q0": "sim_conforme",
                        "step11_q2_form_ebook": "on", "step11_q2_comentarios_venda": "on", "step11_q2_redes_sociais": "on"
                     }
                }
            }
        }
    };

    // --- WIZARD State and DOM Elements ---
    let currentStep = 0;
    let tooltipList = [];
    let collectedFormData = {};
    let autoSaveTimer = null;

    const stepsContainer = document.getElementById('stepsContainer');
    const progressIndicator = document.getElementById('progressIndicator');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const wizardForm = document.getElementById('wizardForm');
    const wizardContainer = document.getElementById('wizardContainer');
    const completionSection = document.getElementById('completionSection');
    const downloadBtn = document.getElementById('downloadBtn');
    const reportThemeSelector = document.getElementById('reportThemeSelector');
    const reportFormatSelector = document.getElementById('reportFormatSelector');
    const apiKeyStatusContainer = document.getElementById('apiKeyStatusContainer');
    const saveProgressBtn = document.getElementById('saveProgressBtn');
    const resetPlanBtn = document.getElementById('resetPlanBtn');
    const validationErrorEl = document.getElementById('validationErrorMessage');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // --- AI Integration Variables ---
    // Remove client-side API key variable and SDK instance
    // let geminiAPIKey = '';
    // let genAI;
    // let aiModel;
    let aiEnabled = HAS_GEMINI_API_KEY_PHP; // Use the value from PHP
    const GEMINI_MODEL_NAME = "gemini-1.5-flash-latest"; // Still needed for server prompt instruction


    // --- Template Variables and DOM ---
    const templateDropdownMenu = document.getElementById('templateDropdownMenu');

    // --- AI Assistance Modal DOM Elements & State ---
    const aiAssistanceModalEl = document.getElementById('aiAssistanceModal');
    const aiAssistanceModalInstance = new bootstrap.Modal(aiAssistanceModalEl);
    const aiSuggestionCountModalEl = document.getElementById('aiSuggestionCountModal');
    const aiAssistanceOutputEl = document.getElementById('aiAssistanceOutput');
    const aiApplyOutputBtn = document.getElementById('aiApplyOutputBtn');
    const aiDiscardOutputBtn = document.getElementById('aiDiscardOutputBtn');
    const aiCopyOutputBtn = document.getElementById('aiCopyOutputBtn');
    const aiCloseModalBtn = document.getElementById('aiCloseModalBtn');

    let currentAiModalOutput = "";
    let modalAiTargetFieldId = null;
    let modalAiIsCkEditorTarget = false;
    let modalAiFriendlyActionName = "";

    // --- INLINE AI ASSISTANCE FOR CKEDITOR ---
    const inlineAiFloatingButtonId = 'inlineAiFloatingButton';
    let inlineAiFloatingButton = null;
    let currentInlineEditorInstance = null; // Stores the CKEditor instance that has the current selection
    let currentInlineEditorId = null; // Stores the ID of the CKEditor (e.g., 'step1_q0')
    let inlineAiDropdown = null; // Bootstrap Dropdown instance for the floating button
    let debounceTimerInlineButton;


    const inlineAiActions = [
        {
            id: 'expandPoint',
            label: 'Expandir este ponto',
            icon: 'bi-arrows-angle-expand',
            promptBuilderKey: 'expandPoint', // Key used to build prompt server-side
            insertionMode: 'afterOrReplace' // 'replace', 'after', 'before', 'showSuggestionsList'
        },
        {
            id: 'rewriteTone',
            label: 'Reescrever com tom...',
            icon: 'bi-arrow-repeat',
            isSubmenu: true,
            subActions: [ /* Populated by populateRewriteToneSubmenu */ ],
        },
        {
            id: 'simplifyLanguage',
            label: 'Simplificar linguagem',
            icon: 'bi-card-text', // Changed icon for variety
            promptBuilderKey: 'simplifyLanguage',
            insertionMode: 'replace'
        },
        {
            id: 'suggestAlternatives',
            label: 'Sugerir alternativas',
            icon: 'bi-lightbulb',
            promptBuilderKey: 'suggestAlternatives',
            insertionMode: 'showSuggestionsList'
        }
    ];

    // Keep prompt definitions here, but they will be sent to the server handler
    const inlineAiPrompts = {
         // Note: Parameters like context are now built and sent in the fetch request
        expandPoint: (selectedText, context) => `Você é um assistente de escrita conciso e direto. Expanda o seguinte ponto/frase de forma detalhada (adicione 2-3 frases relevantes ou um parágrafo curto), mantendo o tom ${context.tone || 'neutro'} e o foco no tema "${context.theme || 'não definido'}".\n\nTexto Original: "${selectedText}"\n\nResultado Esperado: Forneça APENAS o texto expandido ou o conteúdo adicional. Não inclua frases como "Claro, aqui está a expansão:" ou repita o texto original desnecessariamente.`,
        rewriteTone: (selectedText, newTone, context) => `Reescreva o seguinte texto com um tom ${newTone}, considerando o tema "${context.theme || 'não definido'}" e o público "${context.audienceLevel || 'geral'}".\n\nTexto Original: "${selectedText}"\n\nResultado Esperado: Forneça APENAS o texto reescrito no novo tom.`,
        simplifyLanguage: (selectedText, context) => `Simplifique a linguagem do seguinte texto, tornando-o mais claro, conciso e acessível para um público ${context.audienceLevel || 'geral'} interessado no tema "${context.theme || 'não definido'}".\n\nTexto Original: "${selectedText}"\n\nResultado Esperado: Forneça APENAS o texto simplificado.`,
        suggestAlternatives: (selectedText, count = 3, context) => `Sugira ${count} alternativas concisas e impactantes para a seguinte frase/título, que está relacionada ao tema "${context.theme || 'não definido'}".\n\nTexto Original: "${selectedText}"\n\nInstruções: Liste cada alternativa em uma nova linha. Não use marcadores (como -, *, 1.) ou qualquer texto introdutório. Apenas as alternativas, uma por linha.`
    };

    function populateRewriteToneSubmenu() {
        const toneQuestion = steps.find(s => s.title.startsWith("7."))?.questions.find(q => q.id === 'step6_q0');
        if (toneQuestion && toneQuestion.options) {
            const rewriteToneAction = inlineAiActions.find(a => a.id === 'rewriteTone');
            if (rewriteToneAction) {
                rewriteToneAction.subActions = toneQuestion.options
                    .filter(opt => opt.value)
                    .map(opt => ({
                        id: `rewriteTone_${opt.value}`,
                        label: opt.label,
                        icon: 'bi-mic', // Placeholder icon for sub-action
                        originalToneValue: opt.label, // Send label to AI, not value
                        promptBuilderKey: 'rewriteTone', // Uses the generic rewriteTone prompt builder
                        insertionMode: 'replace'
                    }));
            }
        }
    }


    function createInlineAiFloatingButton() {
        if (document.getElementById(inlineAiFloatingButtonId)) {
            inlineAiFloatingButton = document.getElementById(inlineAiFloatingButtonId);
        } else {
            inlineAiFloatingButton = document.createElement('div'); // Use div for easier styling as button group
            inlineAiFloatingButton.id = inlineAiFloatingButtonId;
            // Initial classes for Bootstrap dropdown structure. 'd-none' to hide initially.
            inlineAiFloatingButton.className = 'btn-group d-none';
            inlineAiFloatingButton.style.position = 'absolute';
            inlineAiFloatingButton.style.zIndex = '1056';
            inlineAiFloatingButton.setAttribute('role', 'group');

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-primary rounded-circle p-0';
            button.style.width = '32px';
            button.style.height = '32px';
            button.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.innerHTML = `<i class="bi bi-magic" style="font-size: 1rem; line-height: 1;"></i>`;
            button.setAttribute('data-bs-toggle', 'dropdown');
            button.setAttribute('data-bs-auto-close', 'outside'); // Keep open if submenu is clicked
            button.setAttribute('aria-expanded', 'false');
            inlineAiFloatingButton.appendChild(button);

            const dropdownMenuEl = document.createElement('ul');
            dropdownMenuEl.className = 'dropdown-menu shadow-lg';
            // Populate menu items
            inlineAiActions.forEach(action => {
                const li = document.createElement('li');
                if (action.isSubmenu) {
                    li.className = 'dropdown-submenu';
                    const link = document.createElement('a');
                    link.className = 'dropdown-item dropdown-toggle';
                    link.href = '#';
                    link.setAttribute('role', 'button');
                    link.setAttribute('data-bs-toggle', 'dropdown'); // For Bootstrap 5 submenu
                    link.innerHTML = `<i class="${action.icon}"></i> ${action.label}`;
                    li.appendChild(link);

                    const subMenu = document.createElement('ul');
                    subMenu.className = 'dropdown-menu';
                    action.subActions.forEach(subAction => {
                        const subLi = document.createElement('li');
                        const subLink = document.createElement('a');
                        subLink.className = 'dropdown-item';
                        subLink.href = '#';
                        subLink.dataset.actionId = subAction.id;
                        subLink.dataset.actionSpecificParam = subAction.originalToneValue;
                        subLink.innerHTML = `<i class="${subAction.icon || 'bi-dash'}"></i> ${subAction.label}`;
                        subLi.appendChild(subLink);
                        subMenu.appendChild(subLi);
                    });
                    li.appendChild(subMenu);

                } else {
                    const link = document.createElement('a');
                    link.className = 'dropdown-item';
                    link.href = '#';
                    link.dataset.actionId = action.id;
                    link.innerHTML = `<i class="${action.icon}"></i> ${action.label}`;
                    li.appendChild(link);
                }
                dropdownMenuEl.appendChild(li);
            });

            inlineAiFloatingButton.appendChild(dropdownMenuEl);
            document.body.appendChild(inlineAiFloatingButton);
            inlineAiDropdown = new bootstrap.Dropdown(button); // Initialize on the button part

            // Event listener for menu item clicks
            dropdownMenuEl.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                const targetLink = event.target.closest('a.dropdown-item[data-action-id]');
                if (targetLink && currentInlineEditorInstance) {
                    const actionId = targetLink.dataset.actionId;
                    const actionSpecificParam = targetLink.dataset.actionSpecificParam; // e.g., tone value

                    let actionToExecute = inlineAiActions.find(a => a.id === actionId);
                    if (!actionToExecute && actionId.startsWith('rewriteTone_')) {
                        const parentAction = inlineAiActions.find(a => a.id === 'rewriteTone');
                        actionToExecute = parentAction?.subActions?.find(sa => sa.id === actionId);
                    }

                    if (actionToExecute) {
                        await handleInlineAIAction(currentInlineEditorInstance, actionToExecute, actionSpecificParam);
                    }
                    inlineAiDropdown.hide();
                    hideInlineAiButton();
                }
            });
        }
    }

    function updateInlineAiFloatingButtonPosition(editor) {
        if (!inlineAiFloatingButton || editor.model.document.selection.isCollapsed) {
            hideInlineAiButton();
            return;
        }

        const view = editor.editing.view;
        const viewSelection = view.document.selection;
        const firstRange = viewSelection.getFirstRange();

        if (!firstRange) {
            hideInlineAiButton();
            return;
        }

        try {
            // Get the DOM range from the view range
            const domRange = view.domConverter.viewRangeToDom(firstRange);
            if (!domRange) { hideInlineAiButton(); return; }

            const editableElement = view.domConverter.viewToDom(view.document.getRoot());
            if (!editableElement || !editableElement.contains(domRange.startContainer)) {
                 hideInlineAiButton(); return;
            }

            const rect = domRange.getBoundingClientRect();
            const editorRect = editableElement.getBoundingClientRect();

            // Position the button to the top-right of the selection, relative to the viewport
            let top = window.scrollY + rect.top - (inlineAiFloatingButton.offsetHeight);
            let left = window.scrollX + rect.right;

            // Adjust if out of editor bounds or too close to edge
            top = Math.max(top, window.scrollY + editorRect.top); // Don't go above editor
            left = Math.min(left, window.scrollX + editorRect.right - inlineAiFloatingButton.offsetWidth - 5); // Don't go past editor right
            left = Math.max(left, window.scrollX + editorRect.left + 5); // Don't go past editor left

            inlineAiFloatingButton.style.top = `${top - 5}px`; // Small offset above
            inlineAiFloatingButton.style.left = `${left}px`;
            inlineAiFloatingButton.classList.remove('d-none');
            inlineAiFloatingButton.style.opacity = '1';
            inlineAiFloatingButton.style.transform = 'scale(1)';

        } catch (error) {
            console.warn("Error calculating inline AI button position:", error);
            hideInlineAiButton();
        }
    }


    function showInlineAiButton(editor) {
        if (!aiEnabled || !inlineAiFloatingButton) return;
        currentInlineEditorInstance = editor;
        // Find the ID of the current editor based on its DOM element
        const editorElement = editor.ui.view.element;
        if (editorElement) {
             const wrapper = editorElement.closest('.ckeditor-wrapper-class');
             currentInlineEditorId = wrapper ? wrapper.dataset.targetValidationId : null;
        } else {
            currentInlineEditorId = null;
        }
        updateInlineAiFloatingButtonPosition(editor);
    }

    function hideInlineAiButton() {
        if (inlineAiFloatingButton) {
            inlineAiFloatingButton.classList.add('d-none');
            inlineAiFloatingButton.style.opacity = '0';
            inlineAiFloatingButton.style.transform = 'scale(0.8)';
            if(inlineAiDropdown && inlineAiDropdown._isShown) {
                inlineAiDropdown.hide();
            }
        }
        currentInlineEditorInstance = null;
        currentInlineEditorId = null;
    }

    async function handleInlineAIAction(editor, action, actionSpecificParam) {
        if (!editor || !action || !action.promptBuilderKey || !aiEnabled) {
             if (!aiEnabled) showApiConfigWarning();
             return;
        }

        const originalButtonContent = inlineAiFloatingButton.querySelector('button').innerHTML;
        inlineAiFloatingButton.querySelector('button').innerHTML = `<div class="spinner-border spinner-border-sm text-white" role="status" style="width: 1rem; height: 1rem;"></div>`;
        inlineAiDropdown.hide(); // Hide the main dropdown

        try {
            const selection = editor.model.document.selection;
            if (selection.isCollapsed) {
                Swal.fire("Atenção", "Por favor, selecione um trecho de texto primeiro.", "info");
                return;
            }

            const selectedText = getPlainTextFromSelection(editor, selection);
            if (!selectedText.trim()) {
                Swal.fire("Atenção", "A seleção está vazia ou contém apenas espaços.", "info");
                return;
            }

            const context = buildFullContext(); // Reuse existing context builder
            // Build the prompt text client-side
             const promptFn = inlineAiPrompts[action.promptBuilderKey];
            if (!promptFn) {
                console.error(`Construtor de prompt não encontrado para: ${action.promptBuilderKey}`);
                Swal.fire("Erro", "Ação de IA não configurada corretamente.", "error");
                return;
            }
            const promptText = promptFn(selectedText, context, 3, actionSpecificParam); // actionSpecificParam is tone label for rewrite


            // Make the API call to the server handler
            const aiSuggestionText = await getGeminiSuggestions({
                prompt: promptText,
                actionType: 'inline', // Indicate this is an inline request
                inlineActionId: action.id, // Pass the specific inline action ID
                inlineTargetText: selectedText, // Pass the selected text
                inlineActionParam: actionSpecificParam, // Pass tone or other param
                context: context // Pass the full context if needed server-side
            }, 1, true); // Request 1 suggestion, return raw text

            if (!aiSuggestionText) {
                 if (!aiEnabled) { // If AI got disabled during the call (e.g. API key error)
                     showApiConfigWarning();
                 } else {
                    Swal.fire("Sem Sugestão", "A IA não retornou uma sugestão para esta ação.", "info");
                 }
                return;
            }

             // Assuming getGeminiSuggestions now returns the raw text directly when returnRawText is true
            const resultText = aiSuggestionText;


            if (action.insertionMode === 'showSuggestionsList') {
                const alternatives = resultText.split('\n').map(s => s.trim()).filter(Boolean);
                if (alternatives.length > 0) {
                    const { value: chosenAlternative } = await Swal.fire({
                        title: 'Escolha uma Alternativa',
                        input: 'radio',
                        inputOptions: alternatives.reduce((obj, item) => {
                            obj[item] = item;
                            return obj;
                        }, {}),
                        inputValidator: (value) => !value && 'Você precisa escolher uma opção!',
                        confirmButtonText: 'Aplicar Selecionada',
                        showCancelButton: true,
                        cancelButtonText: 'Cancelar'
                    });

                    if (chosenAlternative) {
                        editor.model.change(writer => {
                            const currentSelectionRanges = Array.from(selection.getRanges());
                            currentSelectionRanges.forEach(range => {
                                writer.remove(range);
                                writer.insertText(chosenAlternative, null, { forcePlainText: true }); // Force plain text for alternatives
                            });
                        });
                        Swal.fire('Aplicado!', 'Alternativa aplicada ao texto.', 'success');
                    }
                } else {
                     Swal.fire("Sem Alternativas", "A IA não forneceu alternativas válidas.", "info");
                }
            } else {
                editor.model.change(writer => {
                    const currentSelectionRanges = Array.from(selection.getRanges());
                    currentSelectionRanges.forEach(range => {
                        if (action.insertionMode === 'replace') {
                            writer.remove(range);
                            writer.insertText(resultText, range.start);
                        } else if (action.insertionMode === 'afterOrReplace') {
                            // If selection is a full paragraph, replace. Otherwise, append.
                            const selectedElement = selection.getSelectedElement();
                            if (selectedElement && editor.model.schema.isBlock(selectedElement)) {
                                writer.remove(range);
                                writer.insertText(resultText, range.start);
                            } else {
                                writer.insertText(" " + resultText, range.end); // Add space before appending
                            }
                        } else if (action.insertionMode === 'after') {
                             writer.insertText(" " + resultText, range.end);
                        } else if (action.insertionMode === 'before') {
                             writer.insertText(resultText + " ", range.start);
                        }
                        // More modes can be added
                    });
                });
                // Simple visual feedback in editor
                const editorUIView = editor.editing.view.document.getRoot();
                if(editorUIView) {
                    const domElem = editor.editing.view.domConverter.mapViewToDom(editorUIView);
                    if(domElem) {
                        domElem.classList.add('ai-content-updated-flash');
                        setTimeout(() => domElem.classList.remove('ai-content-updated-flash'), 1000);
                    }
                }
            }

        } catch (error) {
            console.error("Erro durante ação de IA inline:", error);
            Swal.fire("Erro na IA", `Ocorreu um erro: ${error.message}`, "error");
        } finally {
            inlineAiFloatingButton.querySelector('button').innerHTML = `<i class="bi bi-magic" style="font-size: 1rem; line-height: 1;"></i>`; // Restore icon
            // Don't immediately hide, let user interact? Or hide after confirmation?
            // Let's hide after action/error.
            hideInlineAiButton();
        }
    }

    function getPlainTextFromSelection(editor, selection) {
        const fragment = editor.model.getSelectedContent(selection);
        let plainText = '';
        for (const item of fragment.getChildren()) {
            if (item.is('$text') || item.is('$textProxy')) {
                plainText += item.data;
            } else if (item.is('element') && item.name === 'paragraph') { // Handle paragraphs
                for (const child of item.getChildren()) {
                    if (child.is('$text') || child.is('$textProxy')) {
                        plainText += child.data;
                    }
                }
                plainText += '\n'; // Add newline for paragraphs
            }
            // Could add more complex handling for other element types if needed
        }
        return plainText.trim();
    }

    function showApiConfigWarning() {
         Swal.fire({
            icon: 'warning',
            title: 'IA Não Configurada',
            html: 'As funcionalidades de IA não estão habilitadas. Por favor, configure sua API Key do Gemini no seu <a href="' + BASE_URL_JS + 'profile.php">Perfil</a>.',
            confirmButtonText: 'Entendido'
        });
        updateAPIKeyStatusUI();
    }

    // --- Helper to strip HTML (for text/md reports from CKEditor) ---
    function stripHtml(html) {
        if (!html) return "";
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    // --- Persistence Functions (Using Local Storage) ---
    // NOTE: These functions use client-side localStorage only.
    // For server-side persistence, these would need to be replaced
    // with fetch calls to backend save/load endpoints.
    function saveState(showStatus = false) {
        Object.keys(ckEditorInstances).forEach(editorId => {
            if (ckEditorInstances[editorId] && document.getElementById(editorId)) {
                collectedFormData[editorId] = ckEditorInstances[editorId].getData();
            }
        });

        // Also collect data from non-CKEditor fields on the current step
        collectStepData(); // Ensure all fields from the current step are collected

        try {
            const state = {
                currentStep: currentStep,
                formData: collectedFormData
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            if (showStatus) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Progresso salvo localmente!',
                    html: `Salvo às ${new Date().toLocaleTimeString()}<br><small>Apenas neste navegador.</small>`,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        } catch (error) {
            console.error("Erro ao salvar estado localmente:", error);
             Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Erro ao salvar progresso localmente.',
                showConfirmButton: false,
                timer: 3000
            });
        }
         clearTimeout(autoSaveTimer);
    }

    function loadState() {
        try {
            const savedStateJSON = localStorage.getItem(STORAGE_KEY);
            if (savedStateJSON) {
                const savedState = JSON.parse(savedStateJSON);
                // Basic validation
                if (savedState && typeof savedState.currentStep === 'number' && savedState.formData && typeof savedState.formData === 'object') {
                    currentStep = savedState.currentStep;
                    collectedFormData = savedState.formData;
                    console.log("State loaded from localStorage", {currentStep, formData: collectedFormData});
                    return true;
                } else {
                     console.warn("Invalid state structure in localStorage.");
                     localStorage.removeItem(STORAGE_KEY); // Clear invalid state
                }
            }
        } catch (error) {
            console.error("Erro ao carregar estado localmente:", error);
            localStorage.removeItem(STORAGE_KEY); // Clear corrupted state
        }
        console.log("No state loaded from localStorage.");
        return false;
    }

    function clearState(fromTemplateSelection = false) {
        const doClear = () => {
            localStorage.removeItem(STORAGE_KEY);
            currentStep = 0;
            collectedFormData = {};
            Object.keys(ckEditorInstances).forEach(id => {
                if (ckEditorInstances[id]) ckEditorInstances[id].setData('');
            });
             if (!fromTemplateSelection) {
                Swal.fire({
                    icon: 'info',
                    title: 'Plano Limpo',
                    text: 'Todos os dados locais foram removidos.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            renderStep(0);
            hideCompletionSection();
        };

        if (fromTemplateSelection) {
            doClear();
        } else {
            Swal.fire({
                title: 'Limpar Plano?',
                text: "Tem certeza que deseja limpar todo o plano local? Esta ação não pode ser desfeita.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, limpar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    doClear();
                }
            });
        }
    }

    // --- Auto-Save Logic ---
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            saveState(false); // Don't show toast for auto-save
        }, AUTO_SAVE_INTERVAL);
    }

    // Listen for input/change on the steps container for auto-save
    // Note: CKEditor changes are handled via editor.model.document.on('change:data', ...)
    stepsContainer.addEventListener('input', scheduleAutoSave);
    stepsContainer.addEventListener('change', scheduleAutoSave);


    // --- Validation Function ---
    function validateStep(stepIndex) {
        const stepElement = stepsContainer.querySelector(`.wizard-step[data-step-index="${stepIndex}"]`);
        if (!stepElement) return true;

        let isValid = true;
        validationErrorEl.style.display = 'none';
        const requiredQuestions = steps[stepIndex].questions.filter(q => q.required);

        stepElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        stepElement.querySelectorAll('.is-invalid-ckeditor').forEach(el => el.classList.remove('is-invalid-ckeditor'));


        requiredQuestions.forEach(qData => {
            let fieldValid = false;
             const questionId = qData.id;

            if (qData.type === 'radio') {
                const radios = stepElement.querySelectorAll(`input[name="${questionId}"]`);
                const otherInputEl = stepElement.querySelector(`#${questionId}_other_text`);

                let isOtherChecked = false;
                let checkedValue = null;
                radios.forEach(radio => {
                    if (radio.checked) {
                        checkedValue = radio.value;
                        if (checkedValue === 'other') isOtherChecked = true;
                    }
                });

                if (isOtherChecked) {
                    fieldValid = otherInputEl ? otherInputEl.value.trim() !== '' : false;
                } else {
                     fieldValid = checkedValue !== null;
                }


                if (!fieldValid) {
                     radios.forEach(r => r.closest('.form-check')?.classList.add('is-invalid'));
                     if(otherInputEl && isOtherChecked) otherInputEl.classList.add('is-invalid');
                } else {
                    radios.forEach(r => r.closest('.form-check')?.classList.remove('is-invalid'));
                    if(otherInputEl) otherInputEl.classList.remove('is-invalid');
                }
            } else if (qData.type === 'checkbox') {
                 const checkboxes = stepElement.querySelectorAll(`input[name^="${questionId}_"]`);
                 const otherCheckbox = stepElement.querySelector(`#${questionId}_other_trigger`);
                 const otherText = stepElement.querySelector(`#${questionId}_other_text`)?.value.trim();

                 let isOtherChecked = otherCheckbox?.checked;
                 let standardChecked = Array.from(checkboxes).some(cb => cb.checked && !cb.classList.contains('other-option-trigger'));

                 if (isOtherChecked) fieldValid = !!otherText;
                 else fieldValid = standardChecked;

                 if (!fieldValid) {
                    checkboxes.forEach(cb => cb.closest('.form-check')?.classList.add('is-invalid'));
                    const otherInputEl = stepElement.querySelector(`#${questionId}_other_text`);
                    if(otherInputEl && isOtherChecked) otherInputEl.classList.add('is-invalid');
                 } else {
                    checkboxes.forEach(cb => cb.closest('.form-check')?.classList.remove('is-invalid'));
                    const otherInputEl = stepElement.querySelector(`#${questionId}_other_text`);
                    if(otherInputEl) otherInputEl.classList.remove('is-invalid');
                 }
            } else if (qData.type === 'textarea' && ckEditorFieldIds.includes(questionId)) {
                if (ckEditorInstances[questionId]) {
                    const editorData = ckEditorInstances[questionId].getData();
                    // Check if data is effectively empty (e.g., only <p></p> or whitespace)
                    const isEmptyHtml = editorData.replace(/<[^>]*>/g, '').trim() === '';
                    fieldValid = !isEmptyHtml;

                    const editorElement = ckEditorInstances[questionId].ui.view.element;
                    if (editorElement) {
                        const wrapper = editorElement.closest('.ckeditor-wrapper-class');
                        if (!fieldValid) wrapper?.classList.add('is-invalid-ckeditor');
                        else wrapper?.classList.remove('is-invalid-ckeditor');
                    }
                } else {
                    // If CKEditor failed to initialize for a required field, it's invalid
                    fieldValid = false;
                    const textareaElement = stepElement.querySelector(`#${questionId}`);
                    if (textareaElement) textareaElement.classList.add('is-invalid');
                }
            } else {
                const inputElement = stepElement.querySelector(`#${questionId}`);
                if (inputElement) {
                    fieldValid = inputElement.value.trim() !== '';
                    if (!fieldValid) inputElement.classList.add('is-invalid');
                    else inputElement.classList.remove('is-invalid');
                } else {
                    // Should not happen if rendering is correct, assume valid if element not found
                    fieldValid = true;
                }
            }
            if (!fieldValid) isValid = false;
        });

        if (!isValid) {
            validationErrorEl.textContent = 'Por favor, preencha todos os campos obrigatórios marcados.';
            validationErrorEl.style.display = 'block';
            const firstInvalid = stepElement.querySelector('.is-invalid, .is-invalid-ckeditor .ck.ck-editor__main > .ck-editor__editable');
            if (firstInvalid) {
                 if (firstInvalid.classList.contains('ck-editor__editable')) {
                    const ckWrapper = firstInvalid.closest('.ckeditor-wrapper-class');
                    const editorId = ckWrapper?.dataset.targetValidationId;
                    if (editorId && ckEditorInstances[editorId]) {
                         ckEditorInstances[editorId].editing.view.focus();
                         // Scroll the editor wrapper into view
                          const wrapper = ckEditorInstances[editorId].ui.view.element.closest('.ckeditor-wrapper-class');
                          if(wrapper) wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    } else {
                         firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                         firstInvalid.focus(); // Fallback focus
                    }
                 } else {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                 }
            }
        }
        return isValid;
    }

     // --- Loading State Functions ---
     function showLoading(show = true) {
         if (show) {
             loadingOverlay.style.display = 'flex';
             prevBtn.disabled = true;
             nextBtn.disabled = true;
              // Disable AI buttons while loading
             document.querySelectorAll('.btn-ai-action').forEach(btn => btn.disabled = true);
             document.querySelectorAll('.list-group-item-action').forEach(btn => btn.disabled = true);

         } else {
             loadingOverlay.style.display = 'none';
             prevBtn.disabled = currentStep === 0;
             nextBtn.disabled = false;
             // Re-enable AI buttons if AI is enabled
             document.querySelectorAll('.btn-ai-action').forEach(btn => { if(aiEnabled) btn.disabled = false; });
             document.querySelectorAll('.list-group-item-action').forEach(btn => { if(aiEnabled) btn.disabled = false; });
         }
     }

    // --- AI Functions (Modified to use API_HANDLER_URL) ---
    function updateAPIKeyStatusUI() {
        const aiAssistanceTriggerBtn = document.querySelector('[data-bs-target="#aiAssistanceModal"]');
        if (aiEnabled) {
            apiKeyStatusContainer.innerHTML = `✨ Funcionalidades de IA (Gemini) ATIVADAS.`;
            apiKeyStatusContainer.className = 'container-fluid api-ok';
            // Ensure AI buttons are enabled if AI is enabled
             document.querySelectorAll('.btn-ai-action').forEach(btn => btn.disabled = false);
             document.querySelectorAll('.list-group-item-action').forEach(btn => btn.disabled = false);
            if (aiAssistanceTriggerBtn) aiAssistanceTriggerBtn.disabled = false;

        } else {
            apiKeyStatusContainer.innerHTML = `🔑 Funcionalidades de IA (Gemini) desativadas.
                <button type="button" class="btn btn-sm btn-warning ms-2" id="configureApiKeyBtn">Configurar API Key</button>
                <small class="d-block mt-1">Obtenha sua API Key em <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">Google AI Studio</a>.</small>`;
            apiKeyStatusContainer.className = 'container-fluid';
            // Point the config button to the profile page
            document.getElementById('configureApiKeyBtn')?.addEventListener('click', (e) => {
                 e.preventDefault();
                 window.location.href = BASE_URL_JS + 'profile.php';
            });
            // Ensure AI buttons are disabled if AI is not enabled
             document.querySelectorAll('.btn-ai-action').forEach(btn => btn.disabled = true);
             document.querySelectorAll('.list-group-item-action').forEach(btn => btn.disabled = true);
            if (aiAssistanceTriggerBtn) aiAssistanceTriggerBtn.disabled = true;
        }
    }

    // No client-side initialization needed for the SDK
    // function initializeAI() { ... }

    // This function is replaced by directing users to the profile page
    // async function promptForAPIKey() { ... }

    // Modified getGeminiSuggestions to use fetch to the server handler
    async function getGeminiSuggestions(payload, suggestionCount = 3, returnRawText = false) {
        // payload can be promptText (string) or an object with structured data
        // If it's a string, it's likely from the inline AI, build a proper payload
        // If it's an object, it's likely from the modal or AI button, it's already structured
        let fetchPayload;
        if (typeof payload === 'string') {
             fetchPayload = {
                 action: 'planner_ai', // Indicate this is a planner AI request
                 planner_action_type: payload.inlineActionId, // Pass the action ID
                 prompt_text: payload.prompt, // Pass the pre-built prompt text
                 suggestion_count: suggestionCount,
                 // Include context and specific params for server-side logic if needed
                 context: payload.context,
                 inline_target_text: payload.inlineTargetText,
                 inline_action_param: payload.inlineActionParam
             };
        } else { // Assuming payload is already an object from modal/buttons
            fetchPayload = {
                 action: 'planner_ai', // Indicate this is a planner AI request
                 planner_action_type: payload.actionType,
                 prompt_text: payload.prompt, // Pass the pre-built prompt text
                 suggestion_count: suggestionCount,
                 // Include context for server-side logic if needed
                 context: payload.context,
                 // Pass target info if the server needs to know (e.g., for specific prompt building)
                 target_field_id: payload.directTargetFieldId,
                 source_field_id: payload.sourceFieldId
            };
        }


        if (!aiEnabled) {
             // If somehow a call is triggered despite aiEnabled=false, show warning
             if (!returnRawText) showApiConfigWarning();
             throw new Error("AI functionalities are not enabled."); // Throw to stop processing
        }

        let targetEl = null;
        let buttonElement = null;
        let directTargetFieldId = null; // Used for direct field updates (inline/AI button)
        let feedbackAnchorElement = null; // Used for feedback messages (inline/AI button)


        // Extract elements if they were passed in the original call (pre-fetch modification)
        if (payload.targetElementId) targetEl = document.getElementById(payload.targetElementId);
        if (payload.buttonElement) buttonElement = payload.buttonElement;
        if (payload.directTargetFieldId) directTargetFieldId = payload.directTargetFieldId;
        if (payload.feedbackAnchorElement) feedbackAnchorElement = payload.feedbackAnchorElement;

        if (buttonElement) { // For non-inline AI calls (modal/AI button)
            // Loading state handled by the caller functions now (handleAiAssistanceAction, stepsContainer event)
        }
        if (!returnRawText) showLoading(true);


        try {
            const response = await fetch(API_HANDLER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(fetchPayload)
            });

            const result = await response.json();

            if (!response.ok) {
                 // Handle HTTP errors (e.g., 401, 400, 500)
                 let errorDetail = result.error || `HTTP error! status: ${response.status}`;
                 if (response.status === 401) errorDetail = "API Key inválida ou não configurada. Por favor, verifique seu Perfil.";
                 throw new Error(`API Error: ${errorDetail}`);
            }

            if (result.error) {
                 // Handle API-specific errors (like content blocking, quota) returned in the JSON body
                 let errorDetail = result.error;
                 if (errorDetail.includes("API key not valid") || errorDetail.includes("API_KEY_INVALID") || response.status === 401) {
                     errorDetail = `Sua API Key é inválida ou expirou. Verifique no <a href='${BASE_URL_JS}profile.php' target='_blank' rel='noopener noreferrer'>seu Perfil</a> e configure novamente.`;
                     aiEnabled = false; // Disable AI immediately on frontend
                     updateAPIKeyStatusUI(); renderStep(currentStep); // Re-render current step to show disabled buttons
                 } else if (errorDetail.toLowerCase().includes("quota") || errorDetail.includes("429")) {
                    errorDetail = "Você excedeu sua cota da API ou está fazendo muitas requisições. Tente novamente mais tarde.";
                } else if (errorDetail.includes("Content blocked") || errorDetail.includes("safety")) {
                    errorDetail = `A IA bloqueou a resposta por questões de segurança. Tente reformular sua solicitação ou verifique as políticas de uso. Detalhes: ${errorDetail}`;
                }
                 const errorTitle = 'Erro na IA';
                 if (returnRawText) { throw new Error(errorDetail); } // For inline, throw the error
                 else if (targetEl) targetEl.innerHTML = `<div class="alert alert-danger p-2 mt-2" role="alert">${errorDetail}</div>`;
                 else if (directTargetFieldId && buttonElement) {
                     const feedbackContainer = buttonElement.closest('.form-group') || document.body;
                     const errorDiv = document.createElement('div');
                     errorDiv.className = 'alert alert-danger p-2 mt-2';
                     errorDiv.innerHTML = errorDetail;
                     feedbackContainer.appendChild(errorDiv);
                     setTimeout(() => errorDiv.remove(), 10000);
                 }
                 else if (aiAssistanceOutputEl && aiAssistanceModalEl.classList.contains('show')) {
                     aiAssistanceOutputEl.innerHTML = `<div class="alert alert-danger p-3"><strong>${errorTitle}:</strong><br>${errorDetail}</div>`;
                 }
                 else Swal.fire({ icon: 'error', title: errorTitle, html: errorDetail });
                 return returnRawText ? null : undefined; // Return null/undefined on error
            }

            const suggestionsText = result.response; // Assuming the server returns { response: "..." }

            if (returnRawText) {
                return suggestionsText; // Return the raw text for inline actions
            }

            // For non-inline actions (modal/AI button), render suggestions
            if (directTargetFieldId) {
                 // This path might be less common now if AI buttons just fetch and the modal applies
                 // But keeping it for potential direct field updates if needed
                 renderSuggestions(null, suggestionsText, payload.actionType, directTargetFieldId, buttonElement.closest('.form-group'));
             } else if (targetEl) {
                renderSuggestions(targetEl, suggestionsText, payload.actionType);
            } else { // For AI Assistance Modal, store the result
                currentAiModalOutput = suggestionsText;
            }
             return suggestionsText; // Return text even if not raw, for modal processing

        } catch (error) {
            console.error(`Erro ao chamar API para ${payload.actionType || 'ação desconhecida'}:`, error);
             let errorMessage = `Ocorreu um erro na comunicação com a IA: ${error.message || 'Erro desconhecido'}`;
             let errorHtml = error.message?.includes('<a href='); // Check if error message contains HTML link

             if (returnRawText) { throw error; } // For inline, throw the error so handleInlineAIAction can catch it

             if (targetEl) targetEl.innerHTML = `<div class="alert alert-danger p-2 mt-2" role="alert">${errorHtml ? errorMessage : escapeHtml(errorMessage)}</div>`;
             else if (directTargetFieldId && buttonElement) {
                 const feedbackContainer = buttonElement.closest('.form-group') || document.body;
                 const errorDiv = document.createElement('div');
                 errorDiv.className = 'alert alert-danger p-2 mt-2';
                 errorDiv.innerHTML = errorHtml ? errorMessage : escapeHtml(errorMessage);
                 feedbackContainer.appendChild(errorDiv);
                 setTimeout(() => errorDiv.remove(), 10000);
             }
             else if (aiAssistanceOutputEl && aiAssistanceModalEl.classList.contains('show')) {
                 aiAssistanceOutputEl.innerHTML = `<div class="alert alert-danger p-3"><strong>Erro na IA:</strong><br>${errorHtml ? errorMessage : escapeHtml(errorMessage)}</div>`;
             }
             else Swal.fire({ icon: 'error', title: 'Erro na IA', html: errorHtml ? errorMessage : escapeHtml(errorMessage) });
             return returnRawText ? null : undefined; // Return null/undefined on error
        } finally {
            if (buttonElement) {
                 // Loading state restoration handled by the caller now
            }
            if(!returnRawText) showLoading(false);
        }
    }

    // renderSuggestions function remains largely the same, using the text returned by getGeminiSuggestions
    function renderSuggestions(container, text, type, directTargetFieldId = null, feedbackAnchorElement = null) {
        // Ensure 'marked' is available for reviewPlan or other markdown actions
        if (type === 'reviewPlan' && typeof marked === 'undefined') {
             console.error("Marked.js not loaded for rendering Markdown.");
             if (container) {
                 container.innerHTML = '<p class="text-muted">Cannot render Markdown (Marked.js not loaded). Displaying raw text:</p>';
                 container.innerHTML += `<pre>${escapeHtml(text)}</pre>`;
             }
             return;
         }
         if (typeof TurndownService === 'undefined' && (type === 'generateReportMarkdown' || type === 'handleInlineAIAction')) {
             console.warn("TurndownService not loaded. HTML content may not be correctly converted to Markdown/Text.");
         }


        if (container) container.innerHTML = '';
        // Handle potential JSON or Markdown responses first
        if (type === 'reviewPlan') {
             if (container) {
                try {
                    container.innerHTML = marked.parse(text);
                } catch (e) {
                    console.error("Error parsing Markdown:", e);
                     container.innerHTML = '<p class="text-muted">Error rendering Markdown. Displaying raw text:</p>';
                     container.innerHTML += `<pre>${escapeHtml(text)}</pre>`;
                }
             }
             return; // Stop processing after rendering markdown
        }
        if (type === 'titles' || type === 'chapters') {
             try {
                const cleanedText = text.replace(/^```json\s*|\s*```$/g, '').trim();
                const suggestionsData = JSON.parse(cleanedText);
                if (!Array.isArray(suggestionsData)) throw new Error("JSON response is not an array.");

                if (suggestionsData.length === 0 && container) {
                     container.innerHTML = '<p class="text-muted">A IA não retornou sugestões.</p>';
                     return;
                }

                // Handle specific rendering for titles and chapters JSON
                 if (type === 'titles') {
                     suggestionsData.forEach(sugg => {
                         if (!sugg.title) return;
                         createSuggestionItem(
                             `<strong>Título:</strong> <p>${escapeHtml(sugg.title)}</p>${sugg.subtitle ? `<strong>Subtítulo:</strong> <p>${escapeHtml(sugg.subtitle)}</p>` : ''}`,
                             {
                                 text: '✔️ Usar este',
                                 action: () => {
                                     document.getElementById('step3_q0').value = sugg.title || "";
                                     if (sugg.subtitle !== undefined) document.getElementById('step3_q1').value = sugg.subtitle || "";
                                     collectStepData(); saveState();
                                 }
                             }
                         );
                     });
                 } else if (type === 'chapters') {
                     const fullStructure = suggestionsData.map(chap => {
                         let chapStr = `${chap.name || chap.title || 'Capítulo sem nome'}`;
                         if (chap.subtopics && Array.isArray(chap.subtopics) && chap.subtopics.length > 0) {
                             chapStr += `\n${chap.subtopics.map(s => `  - ${s}`).join('\n')}`;
                         }
                         return chapStr;
                     }).join('\n\n');

                     if (container) {
                          createSuggestionItem(
                              `<pre>${escapeHtml(fullStructure)}</pre>`,
                              { text: '✔️ Usar esta Estrutura', action: () => {
                                   document.getElementById('step4_q0').value = suggestionsData.map(c => c.name || c.title).join('\n');
                                   const subtopicsForEditor = suggestionsData
                                     .map(chap => {
                                         let editorContent = `<h2>${escapeHtml(chap.name || chap.title)}</h2>`;
                                         if (chap.subtopics && chap.subtopics.length > 0) {
                                             editorContent += `<ul>${chap.subtopics.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`;
                                         }
                                         return editorContent;
                                     })
                                     .join('<p>&nbsp;</p>');
                                   if (ckEditorInstances['step4_q1'] && subtopicsForEditor) {
                                       ckEditorInstances['step4_q1'].setData(subtopicsForEditor);
                                   } else if (document.getElementById('step4_q1') && subtopicsForEditor) {
                                       let plainTextSubtopics = suggestionsData.map(chap => {
                                         let plain = chap.name || chap.title;
                                         if (chap.subtopics && chap.subtopics.length > 0) {
                                             plain += '\n' + chap.subtopics.map(s => `  - ${s}`).join('\n');
                                         }
                                         return plain;
                                       }).join('\n\n');
                                       document.getElementById('step4_q1').value = plainTextSubtopics;
                                   }
                                   collectStepData(); saveState();
                               }}
                          );
                     }
                 }
                return; // Stop processing after rendering JSON
             } catch (e) {
                  console.error("Error analyzing JSON response for type:", type, e, "Text received:", text);
                  // Fallback to raw text rendering if JSON parsing fails
                  if (container) {
                      container.innerHTML = `<p class="text-muted">Erro ao processar sugestões (${type}, esperava JSON). Exibindo resposta bruta:</p>`;
                      container.innerHTML += `<pre>${escapeHtml(text)}</pre>`;
                  }
                  return;
             }
        }


        // If not JSON or specific markdown action, assume plain text or simple lists
        const lines = text.split('\n').map(line => line.trim()).filter(line => line);

        const createSuggestionItem = (contentHtml, useButtonConfig) => {
            if (!container) return; // Only add to container if provided
            const itemDiv = document.createElement('div');
            itemDiv.className = 'ai-suggestion-item';
            itemDiv.innerHTML = contentHtml;
            if (useButtonConfig) {
                const useButton = document.createElement('button');
                useButton.type = 'button';
                useButton.className = 'btn btn-sm btn-outline-primary btn-use-suggestion mt-2';
                useButton.innerHTML = useButtonConfig.text || '✔️ Usar';
                useButton.onclick = (e) => {
                    useButtonConfig.action();
                    const feedback = document.createElement('span');
                    feedback.className = 'applied-feedback'; feedback.textContent = 'Aplicado!';
                    itemDiv.appendChild(feedback);
                    setTimeout(() => feedback.remove(), 2000);
                     e.stopPropagation();
                };
                itemDiv.appendChild(useButton);
            }
            container.appendChild(itemDiv);
        };

        const showAppliedFeedback = (anchorElement) => {
            if (!anchorElement) anchorElement = document.body; // Fallback to body
            const feedback = document.createElement('span');
            feedback.className = 'applied-feedback';
            feedback.textContent = 'Aplicado!';
            // Position relative to the anchor element
             feedback.style.position = 'absolute';
             feedback.style.top = '0'; // Or calculate more precisely
             feedback.style.right = '0'; // Or calculate more precisely
             feedback.style.transform = 'translate(50%, -50%)'; // Offset to top-right of anchor corner

            anchorElement.style.position = 'relative'; // Ensure anchor is positioned
            anchorElement.appendChild(feedback);
            setTimeout(() => feedback.remove(), 2000);
        };

        try {
            switch (type) {
                 case 'subtopicsFromChapters':
                    if (directTargetFieldId && ckEditorInstances[directTargetFieldId]) {
                        // Attempt to parse the response assuming it's line-by-line with chapters and subtopics
                         const lines = text.split('\n').map(line => line.trim());
                         let htmlContent = '';
                         let currentListItems = [];
                         let inList = false;

                         lines.forEach(line => {
                             if (!line) { // Empty line potentially separates chapters or ends a list
                                if (inList) {
                                    htmlContent += `<ul>${currentListItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
                                    currentListItems = [];
                                    inList = false;
                                }
                                if (htmlContent && !htmlContent.endsWith('<p>&nbsp;</p>') && !htmlContent.endsWith('</ul>')) {
                                    htmlContent += '<p>&nbsp;</p>'; // Add spacing between blocks
                                }
                                return;
                             }

                             const chapterMatch = line.match(/^(?:Cap[íi]tulo|Seção|Parte|Cap|Section)\s*\d*[:\.]?\s*(.*)/i);
                             const subtopicMatch = line.match(/^\s*(?:[-*\u2022•]|\w\)| \d+\.\d+\.?)\s*(.*)/);

                             if (chapterMatch && chapterMatch[1].trim()) {
                                 if (inList) { // Close previous list if any
                                    htmlContent += `<ul>${currentListItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
                                    currentListItems = [];
                                    inList = false;
                                 }
                                 // Add spacing if previous content exists and wasn't just closed list
                                 if (htmlContent && !htmlContent.endsWith('<p>&nbsp;</p>')) htmlContent += '<p>&nbsp;</p>';
                                 htmlContent += `<h2>${escapeHtml(chapterMatch[1].trim())}</h2>`;
                             } else if (subtopicMatch && subtopicMatch[1].trim()) {
                                inList = true;
                                currentListItems.push(subtopicMatch[1].trim());
                             } else { // Treat as paragraph or ignore
                                 if (inList) { // Close previous list
                                    htmlContent += `<ul>${currentListItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
                                    currentListItems = [];
                                    inList = false;
                                    if (htmlContent && !htmlContent.endsWith('<p>&nbsp;</p>')) htmlContent += '<p>&nbsp;</p>';
                                 }
                                 if (line.trim()) { // Add as paragraph if not empty
                                    htmlContent += `<p>${escapeHtml(line.trim())}</p>`;
                                 }
                             }
                         });

                        // Close any pending list at the end
                         if (inList) {
                            htmlContent += `<ul>${currentListItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
                         }


                        ckEditorInstances[directTargetFieldId].setData(htmlContent);
                        collectStepData(); saveState(); // Save after applying
                        if(feedbackAnchorElement) showAppliedFeedback(feedbackAnchorElement);
                    } else if (directTargetFieldId && document.getElementById(directTargetFieldId)) {
                        // Fallback for textarea if CKEditor didn't load
                        document.getElementById(directTargetFieldId).value = text;
                        collectStepData(); saveState();
                        if(feedbackAnchorElement) showAppliedFeedback(feedbackAnchorElement);
                    } else {
                        console.warn(`Target field ${directTargetFieldId} not found for applying AI suggestion of type ${type}.`);
                        // If no target element, maybe show in a general suggestion area?
                        // For now, just log and do nothing visually if no target.
                    }
                    break;

                case 'persona':
                    if (container) {
                        createSuggestionItem(
                            `<pre>${escapeHtml(text)}</pre>`,
                            { text: '📋 Usar como base para Persona', action: () => {
                                const targetEditorId = 'step1_q0';
                                if (ckEditorInstances[targetEditorId]) {
                                    ckEditorInstances[targetEditorId].setData(text.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>'));
                                } else if (document.getElementById(targetEditorId)) {
                                    document.getElementById(targetEditorId).value = text;
                                }
                                collectStepData(); saveState();
                            }}
                        );
                    }
                    break;
                case 'elevatorPitch': case 'problem': case 'painPoint': case 'outcome':
                case 'writingStyle': case 'coverConcept': case 'launchAction':
                     const targetInputIdMap = {
                         elevatorPitch: 'step3_q2', problem: 'step0_q1', painPoint: 'step1_q2',
                         outcome: 'step2_q0', writingStyle: 'step6_q1', coverConcept: 'step9_q0',
                         launchAction: 'step10_q2'
                     };
                     const targetInputId = targetInputIdMap[type];
                     if (!targetInputId || !container) { console.warn(`Target field ${targetInputId} not found or no container for type ${type}.`); if (container) createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null); break; }

                    // Assuming these types return simple lists or single paragraphs
                    lines.forEach((line) => {
                        const cleanedLine = line.replace(/^[\d\.\-\*\s]+/, '').trim(); // Remove list markers if any
                        if(cleanedLine){
                             createSuggestionItem(
                                 `<p>${escapeHtml(cleanedLine)}</p>`,
                                 { text: '✔️ Usar este', action: () => {
                                      // For single line inputs (text/textarea), just set the value
                                      const targetElement = document.getElementById(targetInputId);
                                      if (targetElement) {
                                           targetElement.value = cleanedLine;
                                      } else if (ckEditorInstances[targetInputId]) {
                                           ckEditorInstances[targetInputId].setData(`<p>${cleanedLine.replace(/\n/g, '<br>')}</p>`);
                                      }
                                      collectStepData(); saveState();
                                  }}
                             );
                        }
                    });
                     // Fallback if no individual lines were processed
                     if (container.childElementCount === 0 && lines.length > 0) {
                          const combinedText = lines.join('\n\n'); // Join lines with double newline for rough paragraph separation
                          createSuggestionItem(
                              `<pre>${escapeHtml(combinedText)}</pre>`,
                              { text: '✔️ Usar todo este texto', action: () => {
                                  const targetElement = document.getElementById(targetInputId);
                                  if (targetElement) {
                                       targetElement.value = combinedText;
                                  } else if (ckEditorInstances[targetInputId]) {
                                      ckEditorInstances[targetInputId].setData(`<p>${combinedText.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>')}</p>`);
                                  }
                                  collectStepData(); saveState();
                              }}
                          );
                      }
                      else if (container.childElementCount === 0 && container) {
                          container.innerHTML = '<p class="text-muted">Nenhuma sugestão individualizada ou texto. Exibindo resposta bruta:</p>';
                          container.innerHTML += `<pre>${escapeHtml(text)}</pre>`;
                      }
                    break;
                 case 'extraElements': case 'marketingChannels':
                     const targetCheckboxPrefixMap = { extraElements: 'step4_q2', marketingChannels: 'step10_q1' };
                     const checkboxPrefix = targetCheckboxPrefixMap[type];
                     if(!checkboxPrefix || !container) { console.warn(`Target prefix ${checkboxPrefix} not found or no container for type ${type}.`); if (container) createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null); break; }

                    const listItems = lines.map(line => line.replace(/^[\d\.\-\*\s]+/, '').trim()).filter(Boolean);
                    if(listItems.length > 0){
                         createSuggestionItem(
                             `<ul>${listItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`,
                             { text: '✔️ Marcar Opções Sugeridas', action: () => {
                                 const stepElement = document.querySelector(`.wizard-step.active`); // Target current step
                                 if (!stepElement) { console.error("Could not find active step element."); return;}

                                 listItems.forEach(suggestedItemText => {
                                     const labels = stepElement.querySelectorAll(`input[name^="${checkboxPrefix}_"] + label`);
                                     let foundMatch = false;
                                     labels.forEach(label => {
                                         // Basic fuzzy matching
                                         const normalizedLabel = label.textContent.toLowerCase().trim().replace(/[^\w\s]/g, '');
                                         const normalizedSuggestion = suggestedItemText.toLowerCase().trim().replace(/[^\w\s]/g, '');

                                         // Check if suggestion contains label text or label text contains first few words of suggestion
                                         const labelWords = normalizedLabel.split(/\s+/);
                                         const suggestionWords = normalizedSuggestion.split(/\s+/);

                                         if (normalizedSuggestion.includes(normalizedLabel) ||
                                             (labelWords.length > 0 && suggestionWords.length > 0 &&
                                              normalizedSuggestion.includes(labelWords[0]) && normalizedLabel.includes(suggestionWords[0])) ||
                                              (labelWords.length > 1 && suggestionWords.length > 1 &&
                                               normalizedSuggestion.includes(labelWords[0] + ' ' + labelWords[1]) && normalizedLabel.includes(suggestionWords[0] + ' ' + suggestionWords[1]))
                                            ) {
                                             const input = document.getElementById(label.htmlFor);
                                             if (input && input.type === 'checkbox') { input.checked = true; foundMatch = true; }
                                         }
                                     });

                                     // If no match found among standard options, add to "Other"
                                     if (!foundMatch) {
                                         const otherCheckbox = stepElement.querySelector(`#${checkboxPrefix}_other_trigger`);
                                         const otherText = stepElement.querySelector(`#${checkboxPrefix}_other_text`);
                                         if(otherCheckbox && otherText){
                                             if(!otherCheckbox.checked) {
                                                otherCheckbox.checked = true;
                                                otherText.style.display = 'block'; // Ensure text field is visible
                                             }
                                             // Add to other text if not already present
                                             const currentOtherValue = otherText.value.toLowerCase().trim();
                                             const suggestionClean = suggestedItemText.trim();
                                             if (currentOtherValue) {
                                                 if (!currentOtherValue.includes(suggestionClean.toLowerCase())) {
                                                      otherText.value += `, ${suggestionClean}`;
                                                 }
                                             } else {
                                                otherText.value = suggestionClean;
                                             }
                                         }
                                     }
                                 });
                                 // Dispatch change event on checkboxes and text inputs so state is updated
                                 stepElement.querySelectorAll(`input[name^="${checkboxPrefix}_"], input[name="${checkboxPrefix}_other_text"]`).forEach(input => {
                                     input.dispatchEvent(new Event('change', { bubbles: true }));
                                 });
                                 collectStepData(); saveState(); // Save after applying
                             }}
                         );
                    } else if (container) {
                         container.innerHTML = '<p class="text-muted">Nenhuma sugestão válida. Exibindo resposta bruta:</p>';
                         container.innerHTML += `<pre>${escapeHtml(text)}</pre>`;
                     }
                    break;

                default:
                    console.warn("Tipo de sugestão não tratado para rendering:", type);
                    if (container) container.innerHTML = `<pre>${escapeHtml(text)}</pre>`;
            }
        } catch (error) {
            console.error("Erro ao renderizar sugestões:", error, "Texto recebido:", text);
            if (container) container.innerHTML = `<div class="alert alert-warning p-2 mt-2" role="alert">Erro ao processar a resposta. Exibindo resposta bruta:</div><pre>${escapeHtml(text)}</pre>`;
        }
    }

    function parseChapterSuggestions(text) {
        // This function is likely redundant now if the server is expected to return JSON
        // and the client-side rendering handles the JSON.
        // However, keeping it as a potential fallback if server JSON parsing fails.
        const suggestions = []; let currentChapter = null;
        text.split('\n').forEach(line => {
            const trimmedLine = line.trim();
            // Updated regex to be more flexible with numbering and separators
            const chapterMatch = trimmedLine.match(/^(?:Cap[íi]tulo|Seção|Parte|Cap|Section)\s*\d*[:\.]?\s*(.*)/i);
            const subtopicMatch = trimmedLine.match(/^\s*(?:[-*\u2022•]|\w\)|\d+\.\d+\.?)\s*(.*)/);

            // Treat lines starting with capital letters or numbers followed by space as potential chapters if not already in a chapter context
             const potentialChapterLine = trimmedLine.match(/^([A-ZÀ-ÖØ-Þ]|\d+\.?\s)/);


            if (chapterMatch && chapterMatch[1].trim() && !subtopicMatch && chapterMatch[1].trim().length > 3) {
                 // Found explicit Chapter/Section line
                 if (currentChapter && currentChapter.name) suggestions.push(currentChapter);
                 currentChapter = { name: chapterMatch[1].trim(), subtopics: [] };
            } else if (subtopicMatch && subtopicMatch[1].trim() && currentChapter) {
                 // Found explicit subtopic line within a chapter context
                 const subtopicName = subtopicMatch[1].trim();
                 if(subtopicName) currentChapter.subtopics.push(subtopicName);
            } else if (trimmedLine && potentialChapterLine && trimmedLine.length > 5 && (!currentChapter || currentChapter.subtopics.length > 0)) {
                // Treat as potential new chapter if not explicitly a subtopic,
                // is a non-empty line starting with Capital or Number,
                // and we're not currently processing subtopics of a previous chapter.
                 if (currentChapter && currentChapter.name) suggestions.push(currentChapter);
                 currentChapter = { name: trimmedLine, subtopics: [] };
             } else if (trimmedLine && currentChapter) {
                 // Any other non-empty line within a chapter context might be a subtopic
                 // (less strict parsing if no explicit marker) - optional based on desired robustness
                 // For now, only explicitly marked subtopics are added to lists.
                 // Other lines might be added as paragraphs in the editor render, but not as list items here.
             }
        });
        if (currentChapter && currentChapter.name) suggestions.push(currentChapter);
        return suggestions;
    }


    // --- TEMPLATE Functions ---
    function populateTemplateDropdown() {
        if (!templateDropdownMenu) return;
        templateDropdownMenu.innerHTML = '';

        let li = document.createElement('li');
        let a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.textContent = '-- Começar do Zero --';
        a.dataset.templateKey = '';
        li.appendChild(a);
        templateDropdownMenu.appendChild(li);

        templateDropdownMenu.appendChild(document.createElement('hr'));

        for (const categoryKey in ebookTemplates) {
            const category = ebookTemplates[categoryKey];
            li = document.createElement('li');
            let header = document.createElement('span');
            header.className = 'dropdown-header';
            header.textContent = category.name;
            li.appendChild(header);
            templateDropdownMenu.appendChild(li);

            if (category.subcategories) {
                for (const subcatKey in category.subcategories) {
                    const subcategory = category.subcategories[subcatKey];
                    li = document.createElement('li');
                    let subHeader = document.createElement('span');
                    subHeader.className = 'dropdown-item subcategory-header';
                    subHeader.textContent = subcategory.name;
                    li.appendChild(subHeader);
                    templateDropdownMenu.appendChild(li);

                    for (const templateKey in subcategory.templates) {
                        const template = subcategory.templates[templateKey];
                        li = document.createElement('li');
                        a = document.createElement('a');
                        a.className = 'dropdown-item template-item';
                        a.href = '#';
                        a.textContent = template.name;
                        a.dataset.templateKey = `${categoryKey}.${subcatKey}.${templateKey}`;
                        li.appendChild(a);
                        templateDropdownMenu.appendChild(li);
                    }
                }
            }

            if (category.templates) {
                 for (const templateKey in category.templates) {
                    const template = category.templates[templateKey];
                    li = document.createElement('li');
                    a = document.createElement('a');
                    a.className = 'dropdown-item template-item';
                    a.href = '#';
                    a.textContent = template.name;
                    a.dataset.templateKey = `${categoryKey}.${templateKey}`;
                    li.appendChild(a);
                    templateDropdownMenu.appendChild(li);
                }
            }
        }
    }

    function getTemplateDataByKey(fullKey) {
        if (!fullKey) return null;
        const parts = fullKey.split('.');
        let currentLevel = ebookTemplates;
        let currentData = null;

        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            if (currentLevel[part]) {
                currentData = currentLevel[part]; // Keep track of the current object
                if (i === parts.length - 1) {
                    return currentData; // Found the template/category object
                }
                 // Navigate deeper
                if (currentData.subcategories && currentData.subcategories[parts[i+1]]) {
                    currentLevel = currentData.subcategories;
                } else if (currentData.templates && currentData.templates[parts[i+1]]) {
                    currentLevel = currentData.templates;
                } else {
                    return null; // Path doesn't match structure
                }
            } else {
                return null; // Part not found at current level
            }
        }
        return null; // Should not be reached if logic is correct, but for safety
    }


    async function applyTemplate(fullTemplateKey) {
        const template = getTemplateDataByKey(fullTemplateKey);
         const isClearAction = !fullTemplateKey;

        if (isClearAction) {
             if (Object.keys(collectedFormData).length > 0) {
                 const result = await Swal.fire({
                    title: 'Limpar Plano Atual?',
                    text: "Deseja limpar o plano atual e começar um novo do zero?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, limpar',
                    cancelButtonText: 'Não, manter'
                });
                if (!result.isConfirmed) {
                    return;
                }
            }
            clearState(true); // true flag prevents Swal success message inside clearState
             Swal.fire({
                 icon: 'info',
                 title: 'Plano Limpo',
                 text: 'Todos os dados locais foram removidos.',
                 timer: 2000,
                 showConfirmButton: false
             });
            wizardContainer.style.display = 'block';
            hideCompletionSection();
            return;
        }


        if (!template || !template.data) {
            console.error("Template não encontrado ou inválido:", fullTemplateKey);
             Swal.fire('Erro', 'Modelo não encontrado ou inválido.', 'error');
            return;
        }

        if (Object.keys(collectedFormData).length > 0) {
            const result = await Swal.fire({
                title: 'Aplicar Modelo?',
                html: `Aplicar o modelo "<strong>${escapeHtml(template.name)}</strong>" substituirá os dados atuais do seu plano local. Deseja continuar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, aplicar!',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) {
                return;
            }
        }

        // Clear current state before applying template data
        clearState(true); // Use silent clear

        // Apply template data
        collectedFormData = { ...template.data };
        currentStep = 0;
        saveState(); // Save the applied template data

        // Re-render the current step to show applied data
        await renderStep(currentStep); // Use await because renderStep is async

        wizardContainer.style.display = 'block';
        hideCompletionSection();

        Swal.fire({
            icon: 'success',
            title: 'Modelo Aplicado!',
            html: `O modelo "<strong>${escapeHtml(template.name)}</strong>" foi aplicado localmente.<br><br><strong>Atenção:</strong> Revise e ajuste os dados aplicados, substituindo os placeholders como '[Nome do Software]', '[Persona]', etc., pelos seus próprios dados.`,
            showConfirmButton: true
        });
    }


    // --- AI ASSISTANCE MODAL Functions ---
    function resetModalUI() {
        aiAssistanceOutputEl.innerHTML = 'O resultado da IA será exibido aqui.';
        aiApplyOutputBtn.style.display = 'none';
        aiDiscardOutputBtn.style.display = 'none';
        aiCopyOutputBtn.style.display = 'none';
        aiCloseModalBtn.textContent = 'Fechar';
        currentAiModalOutput = "";
        modalAiTargetFieldId = null;
        modalAiIsCkEditorTarget = false;
        modalAiFriendlyActionName = "";
        // Re-enable modal action buttons
         aiAssistanceModalEl.querySelectorAll('.list-group-item-action').forEach(btn => {
             if(aiEnabled) btn.disabled = false;
             btn.classList.remove('loading'); // Remove loading class if any got stuck
         });
    }

    function buildFullContext() {
        collectStepData(); // Ensure all fields from the current step are in collectedFormData
        let context = {
            theme: collectedFormData['step0_q0'] || "Não definido",
            problem: collectedFormData['step0_q1'] || "Não definido",
            personaDesc: stripHtml(collectedFormData['step1_q0']) || "Não definido", // Use stripped HTML for AI prompt
            audienceLevel: getOptionLabel('step1_q1', collectedFormData['step1_q1']) || "Não definido",
            readerOutcome: collectedFormData['step2_q0'] || "Não definido",
            ebookObjective: getOptionLabel('step2_q1', collectedFormData['step2_q1']) || "Não definido",
            title: collectedFormData['step3_q0'] || "Não definido",
            subtitle: collectedFormData['step3_q1'] || "Não definido",
            mainChapters: collectedFormData['step4_q0'] || "Não definidos", // Use plain text list
            detailedToc: stripHtml(collectedFormData['step4_q1']) || "Não definidos", // Use stripped HTML for AI prompt
            tone: getOptionLabel('step6_q0', collectedFormData['step6_q0']) || "Não definido",
            distributionModel: getOptionLabel('step10_q0', collectedFormData['step10_q0']) || "Não definido",
            marketingChannelsList: [],
        };

        // Build marketing channels list correctly from collected data
        const marketingChannelsQuestion = steps.flatMap(s => s.questions).find(q => q.id === 'step10_q1');
        if (marketingChannelsQuestion && marketingChannelsQuestion.options) {
            marketingChannelsQuestion.options.forEach(opt => {
                if (collectedFormData[`step10_q1_${opt.value}`] === 'on') {
                    context.marketingChannelsList.push(opt.label);
                }
            });
            if (collectedFormData['step10_q1_other'] === 'on' && collectedFormData['step10_q1_other_text']) {
                context.marketingChannelsList.push(`Outro: ${collectedFormData['step10_q1_other_text']}`);
            }
        }
        if(context.marketingChannelsList.length === 0) context.marketingChannelsList = "Não definidos";
        else context.marketingChannelsList = context.marketingChannelsList.join(', ');

        return context;
    }


    async function handleAiAssistanceAction(actionType) {
        if (!aiEnabled) {
             showApiConfigWarning();
             aiAssistanceModalInstance.hide(); // Hide modal if trying to use AI when disabled
            return;
        }

        const context = buildFullContext();
        const suggestionCount = parseInt(aiSuggestionCountModalEl.value) || 3;

        let prompt = "";
        modalAiTargetFieldId = null; // Reset target field
        modalAiIsCkEditorTarget = false; // Reset target type
        modalAiFriendlyActionName = ""; // Reset action name
        let expectsJson = false;
        let markdownAction = false;


        const clickedButton = aiAssistanceModalEl.querySelector(`button[data-action-type="${actionType}"]`);
         if (clickedButton) {
            modalAiFriendlyActionName = clickedButton.querySelector('h6')?.textContent?.trim() || actionType;
             // Add loading state to the clicked button
             clickedButton.classList.add('loading');
             clickedButton.disabled = true; // Disable while loading
             // Disable all other modal action buttons
             aiAssistanceModalEl.querySelectorAll('.list-group-item-action').forEach(btn => {
                 if (btn !== clickedButton) btn.disabled = true;
             });
         }


        aiAssistanceOutputEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Gerando...</span></div><p class="mt-2">A IA está processando sua solicitação...</p></div>';
        aiApplyOutputBtn.style.display = 'none';
        aiDiscardOutputBtn.style.display = 'none';
        aiCopyOutputBtn.style.display = 'none';
        aiCloseModalBtn.textContent = 'Fechar'; // Change back to 'Fechar' while loading

        // --- Prompt Building Logic (Keep in JS for now) ---
        switch (actionType) {
            case 'generateIntroduction':
                prompt = `Você é um assistente de escrita. Baseado no plano abaixo, escreva um rascunho de INTRODUÇÃO de aproximadamente 300-500 palavras.
**Plano:**
- Título: ${context.title}
- Tema: ${context.theme}
- Problema: ${context.problem}
- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})
- Resultado: ${context.readerOutcome}
- Capítulos: ${context.mainChapters || "Não definidos"}
- Tom: ${context.tone}
**Instruções:** Gancho inicial, apresente problema/oportunidade, a solução (eBook), breve panorama dos capítulos, credibilidade (opcional), chamada para leitura. Mantenha o tom planejado.
Formato: Texto corrido.`;
                break;
            case 'reviewPlan':
                markdownAction = true;
                prompt = `Você é um editor experiente. Analise o plano abaixo e forneça feedback construtivo sobre:
1. Alinhamento (título, problema, público, resultado, capítulos)
2. Clareza da Proposta de Valor
3. Completude dos Capítulos (faltam? redundantes?)
4. Engajamento do Título/Subtítulo
5. CTA (implícita/explícita, considerando o objetivo do autor)
6. Sugestões Gerais para Melhoria (${suggestionCount} principais)
**Plano:**
- Tema: ${context.theme}
- Problema: ${context.problem}
- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})
- Objetivo Autor: ${context.ebookObjective}
- Resultado Leitor: ${context.readerOutcome}
- Título: ${context.title}
- Subtítulo: ${context.subtitle}
- Capítulos: ${context.mainChapters || "Não definidos"}
- TOC Detalhada (se houver): ${context.detailedToc.substring(0,300)}${context.detailedToc.length > 300 ? '...' : ''}
- Tom: ${context.tone}
Formato: Tópicos com markdown. Use linguagem clara e acionável.`;
                break;
            case 'generateSummary':
                modalAiTargetFieldId = 'step4_q0';
                expectsJson = true;
                prompt = `Baseado no contexto, gere uma lista de ${suggestionCount} TÍTULOS DE CAPÍTULOS principais para um eBook.
**Contexto:**
- Tema: ${context.theme}
- Problema que o eBook resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
- Resultado esperado para o leitor: ${context.readerOutcome}
**Instruções:** Os títulos dos capítulos devem ser lógicos, sequenciais e atraentes.
Formato da Resposta (JSON Array de Strings EXATO):
["Título do Capítulo 1", "Título do Capítulo 2", ...]`;
                break;
            case 'generateDetailedTOC':
                modalAiTargetFieldId = 'step4_q1';
                modalAiIsCkEditorTarget = true;
                expectsJson = true;
                const subtopicCountModal = parseInt(aiSuggestionCountModalEl.value) || 3; // Use modal count for subtopics

                prompt = `Gere uma ESTRUTURA DE CAPÍTULOS E SUB-TÓPICOS detalhada para um eBook.
${context.mainChapters && context.mainChapters !== "Não definidos" ? `Use esta lista de capítulos como base:\n${context.mainChapters}\nPara cada um, detalhe ${subtopicCountModal} sub-tópicos relevantes.` : `Crie ${suggestionCount} capítulos principais e, para cada capítulo, detalhe ${subtopicCountModal} sub-tópicos relevantes.`}
**Contexto:**
- Tema: ${context.theme}
- Problema que o eBook resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
- Resultado esperado para o leitor: ${context.readerOutcome}
**Instruções:** Os sub-tópicos devem ser específicos, lógicos e cobrir os aspectos essenciais de cada capítulo.
Formato da Resposta (JSON Array de Objetos EXATO):
[
  { "title": "Título do Capítulo 1", "subtopics": ["Subtópico 1.1", "Subtópico 1.2"] },
  { "title": "Título do Capítulo 2", "subtopics": ["Subtópico 2.1", "Subtópico 2.2"] }
  ...
]`;
                break;
            case 'analyzeTitleSubtitle':
                markdownAction = true;
                prompt = `Analise o título e subtítulo abaixo para um eBook, considerando engajamento, clareza e potencial de SEO.
**Contexto do eBook:**
- Tema: ${context.theme}
- Problema que resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
**Título Atual:** ${context.title}
**Subtítulo Atual:** ${context.subtitle}
**Instruções:**
Forneça uma análise em tópicos:
1.  **Pontos Fortes:** O que funciona bem?
2.  **Pontos a Melhorar:** Onde pode ser mais claro, mais atraente ou melhor para SEO?
3.  **Sugestões (${suggestionCount} opções):** Apresente alternativas ou melhorias para o título e/ou subtítulo.
Formato: Markdown.`;
                break;
            case 'generateMarketingDescription':
                prompt = `Crie ${suggestionCount} opções de descrição curta (sinopse) para um eBook, ideal para uso em lojas online (Amazon, Hotmart) ou posts de redes sociais.
**Informações do eBook:**
- Título: ${context.title}
- Subtítulo: ${context.subtitle}
- Problema que resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
- Principal benefício/resultado para o leitor: ${context.readerOutcome}
- Tom de voz: ${context.tone}
**Instruções:** Cada descrição deve ser persuasiva, destacar o benefício principal e ter entre 50-150 palavras. Mantenha o tom de voz planejado. Separe cada opção com "---".
Opção 1:
[Texto da descrição 1]
---
Opção 2:
[Texto da descrição 2]
...`;
                break;
            case 'suggestSeoKeywords':
                prompt = `Sugira uma lista de ${suggestionCount} palavras-chave relevantes para otimização de SEO de um eBook e seu material de divulgação.
**Informações do eBook:**
- Tema Principal: ${context.theme}
- Problema que resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
**Instruções:**
Liste palavras-chave primárias (alto volume, mais genéricas) e secundárias (long-tail, mais específicas). Indique a intenção de busca (informacional, transacional, etc.) se possível.
Formato: Liste cada palavra-chave ou frase em uma nova linha, opcionalmente com a intenção.
[Palavra-chave 1] (Intenção)
[Palavra-chave Long-Tail 1] (Intenção)
...`; // Simplified format instruction
                break;
            case 'brainstormSupportContent':
                prompt = `Gere ${suggestionCount} ideias para conteúdo de apoio (artigos de blog, posts de redes sociais, vídeos) para promover um eBook.
**Informações do eBook:**
- Tema Principal: ${context.theme}
- Título do eBook: ${context.title}
- Capítulos Principais (se houver): ${context.mainChapters || "Não definidos, baseie-se no tema."}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}
**Instruções:** As ideias devem ser relevantes ao tema do eBook e interessantes para o público-alvo. Podem ser aprofundamentos de capítulos, bastidores, estudos de caso relacionados, etc. Liste cada ideia como um título ou tema curto.
1. [Ideia 1]
2. [Ideia 2]
...`;
                break;
            case 'analyzePlannedTone':
                markdownAction = true;
                prompt = `Analise o tom de voz planejado para um eBook e sua adequação ao público e tema.
**Informações do eBook:**
- Tom de Voz Planejado: ${context.tone}
- Descrição do Estilo (se houver): ${collectedFormData['step6_q1'] || "Não fornecida"}
- Tema Principal: ${context.theme}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
**Instruções:**
Avalie se o tom planejado é:
1.  Consistente com a descrição de estilo (se houver).
2.  Adequado para o público-alvo e seu nível de conhecimento.
3.  Apropriado para o tema do eBook.
Forneça feedback construtivo e sugestões para refinar o tom, se necessário, incluindo ${suggestionCount} exemplos de como certas frases poderiam ser adaptadas.
Formato: Markdown, com tópicos.`;
                break;

            default:
                aiAssistanceOutputEl.textContent = "Ação de IA não reconhecida.";
                return;
        }
        // --- End Prompt Building ---


        // Call the unified getGeminiSuggestions function
         const apiResponseText = await getGeminiSuggestions({
             actionType: actionType,
             prompt: prompt, // Pass the pre-built prompt
             context: context, // Pass context if needed server-side (e.g. for specific prompt building or verification)
             suggestionCount: suggestionCount
         }, suggestionCount, false); // Don't return raw text, let getGeminiSuggestions handle rendering to modal if container passed

        // After getGeminiSuggestions completes (or fails), update the modal UI
        if (clickedButton) {
             clickedButton.classList.remove('loading');
             clickedButton.disabled = !aiEnabled; // Re-enable if AI is still enabled
             // Re-enable all other modal action buttons
             aiAssistanceModalEl.querySelectorAll('.list-group-item-action').forEach(btn => {
                  btn.disabled = !aiEnabled; // Re-enable all if AI is still enabled
             });
        }


        if (apiResponseText !== undefined && apiResponseText !== null) { // Check if response was successful
            currentAiModalOutput = apiResponseText;

             if (markdownAction && typeof marked !== 'undefined') {
                try {
                    aiAssistanceOutputEl.innerHTML = marked.parse(currentAiModalOutput);
                } catch (e) {
                    console.error("Erro ao renderizar Markdown no modal AI:", e);
                    aiAssistanceOutputEl.innerHTML = `<pre>${escapeHtml(currentAiModalOutput)}</pre>`; // Fallback to pre
                }
            } else if (expectsJson) {
                 // For JSON responses in the modal, just show the raw JSON in a <pre>
                 aiAssistanceOutputEl.innerHTML = `<pre>${escapeHtml(currentAiModalOutput)}</pre>`;
            }
            else {
                 // For plain text, convert newlines to paragraphs/breaks
                 aiAssistanceOutputEl.innerHTML = `<p>${escapeHtml(currentAiModalOutput).replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>')}</p>`;
            }

            // Determine if the output can be applied and show the button
            if (modalAiTargetFieldId) { // Check if the action is associated with a specific field
                 if (actionType === 'generateSummary' || actionType === 'generateDetailedTOC' || actionType === 'generateIntroduction' || actionType === 'generateMarketingDescription' || actionType === 'suggestSeoKeywords' || actionType === 'brainstormSupportContent' || actionType === 'analyzeTitleSubtitle' || actionType === 'analyzePlannedTone') {
                      // These actions produce content that *might* be applicable, even if not a perfect fit.
                      // Let's show Apply button for these, assuming the user might copy-paste sections.
                      // A more complex implementation would parse the output and offer to apply specific parts.
                      // For simplicity, enable the apply button if a target field was identified.
                     aiApplyOutputBtn.style.display = 'inline-block';
                 } else {
                      aiApplyOutputBtn.style.display = 'none'; // Don't show apply for reviewPlan etc.
                 }
            } else {
                aiApplyOutputBtn.style.display = 'none'; // No target field, no apply button
            }


            aiDiscardOutputBtn.style.display = 'inline-block';
            aiCopyOutputBtn.style.display = 'inline-block';
            aiCloseModalBtn.textContent = 'Fechar'; // Set back to 'Fechar' as result is ready
        } else {
             // If getGeminiSuggestions returned null/undefined (indicating an error was shown inside it)
             if (!aiAssistanceOutputEl.querySelector('.alert-danger')) { // Only show if getGeminiSuggestions didn't already show an error
                aiAssistanceOutputEl.innerHTML = '<div class="alert alert-warning p-3">Não foi possível gerar a sugestão ou a resposta da IA foi vazia. Tente novamente.</div>';
             }
             // Buttons remain hidden except Close
             aiDiscardOutputBtn.style.display = 'none';
             aiCopyOutputBtn.style.display = 'none';
             aiApplyOutputBtn.style.display = 'none';
        }
    }

    // --- Wizard Functions ---
    async function renderStep(index) {
        // Destroy CKEditor instances from the current step before rendering the next/previous one
        const currentStepDefinition = steps[currentStep];
        if (currentStepDefinition) {
            const editorIdsInCurrentStep = currentStepDefinition.questions.filter(q => ckEditorFieldIds.includes(q.id)).map(q => q.id) || [];
            for (const editorId of editorIdsInCurrentStep) {
                if (ckEditorInstances[editorId]) {
                    try {
                        // CKEditor 5's destroy method is async
                        await ckEditorInstances[editorId].destroy();
                        console.log(`CKEditor instance ${editorId} destroyed.`);
                    }
                    catch (err) {
                        // Handle potential errors during destruction (e.g., editor already destroyed)
                        console.warn(`Error destroying CKEditor instance ${editorId}:`, err);
                    }
                    delete ckEditorInstances[editorId];
                }
            }
        }
        // Dispose tooltips from the current step
        tooltipList.forEach(tooltip => tooltip.dispose());
        tooltipList = [];
        hideInlineAiButton(); // Hide button when changing steps


        stepsContainer.innerHTML = '';
        validationErrorEl.style.display = 'none';

        const step = steps[index];
        const stepDiv = document.createElement('div');
        stepDiv.className = 'wizard-step active';
        stepDiv.setAttribute('data-step-index', index);

        const titleEl = document.createElement('h3');
        titleEl.className = 'step-title'; titleEl.textContent = step.title;
        stepDiv.appendChild(titleEl);

        // Use a standard loop or for...of with try...catch for async operations in map
        for (const qData of step.questions) { // Use for...of to await inside
            const formGroup = document.createElement('div');
            formGroup.className = 'mb-4 position-relative';
            let labelWrapper = document.createElement('div');
            labelWrapper.className = 'd-flex justify-content-between align-items-center mb-1 flex-wrap';
            const label = document.createElement('label');
            label.htmlFor = qData.id; label.className = 'form-label mb-0';
            label.innerHTML = qData.label + (qData.required ? '<span class="required-field-marker">*</span>' : '');
            if (qData.tooltip) { label.setAttribute('data-bs-toggle', 'tooltip'); label.setAttribute('data-bs-placement', 'top'); label.title = qData.tooltip; }
            labelWrapper.appendChild(label);

            // Add AI button for specific questions if AI is enabled
            if (aiEnabled && qData.aiSuggestion && qData.aiSuggestion.type !== 'subtopicsFromChapters') {
                 const aiButtonContainer = document.createElement('div');
                 aiButtonContainer.className = 'inline-ai-button-container';

                 // Add count input only if the AI action type makes sense with count
                 const needsCountInput = !['persona', 'coverConcept', 'subtopicsFromChapters'].includes(qData.aiSuggestion.type);
                 if (needsCountInput) {
                      const countInput = document.createElement('input');
                      countInput.type = 'number';
                      countInput.className = 'form-control form-control-sm ai-count-input';
                      countInput.id = `${qData.id}_ai_count`;
                      countInput.min = "1"; countInput.max = "10";
                      countInput.value = qData.aiSuggestion.countDefault || "3";
                      countInput.title = `Número de sugestões (${countInput.min}-${countInput.max})`;
                      aiButtonContainer.appendChild(countInput);
                 }

                 const aiButton = document.createElement('button');
                 aiButton.type = 'button';
                 aiButton.className = 'btn btn-sm btn-outline-info btn-ai-action';
                 aiButton.dataset.questionId = qData.id;
                 aiButton.dataset.suggestionType = qData.aiSuggestion.type;
                 // Removed data-target-div-id; suggestions are now rendered inline where the button is
                 aiButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-magic me-1" viewBox="0 0 16 16"><path d="M9.5 2.672a.5.5 0 1 0 1 0V.843a.5.5 0 0 0-1 0v1.829Zm4.5.035A.5.5 0 0 0 13.293 2L12 3.293a.5.5 0 1 0 .707.707L14 2.707ZM7.293 4A.5.5 0 1 0 8 3.293L6.707 2A.5.5 0 0 0 6 2.707L7.293 4Zm-.621 2.5a.5.5 0 0 0 0-1H4.843a.5.5 0 1 0 0 1h1.829Zm8.485 0a.5.5 0 1 0 0-1h-1.829a.5.5 0 0 0 0 1h1.829ZM13.293 12A.5.5 0 0 0 14 11.293L12.707 10a.5.5 0 1 0-.707.707L13.293 12Zm-5.786 1.328a.5.5 0 1 0-1 0v1.829a.5.5 0 0 0 1 0V13.33Zm-4.5.035a.5.5 0 0 0 .707.707L4 12.707a.5.5 0 0 0-.707-.707L2 13.293a.5.5 0 0 0 .707.707ZM11.5 6.5a.5.5 0 0 0-1 0V8.33a.5.5 0 0 0 1 0V6.5Zm-6.932 1.432a.5.5 0 0 0-.52.045L2.509 9.417a.5.5 0 0 0 .487.878l1.537-1.025a.5.5 0 0 0 .045-.52ZM12.94 9.417l1.538 1.025a.5.5 0 0 0 .487-.878l-1.537-1.025a.5.5 0 0 0-.52-.045L10.5 9.831l.03-.055a.5.5 0 0 0-.03.055l2.44 1.626Z"/></svg>
                     <span>${qData.aiSuggestion.buttonText || 'Sugerir'}</span>
                     <span class="spinner-border spinner-border-sm ms-2 align-middle"></span>`;

                 // Add event listener directly during render
                 aiButton.addEventListener('click', async (event) => {
                      const button = event.currentTarget;
                      const type = button.dataset.suggestionType;
                      const qId = button.dataset.questionId;
                      const cntInput = button.parentElement.querySelector('.ai-count-input');
                      const count = cntInput ? parseInt(cntInput.value) : 1; // Default to 1 if no count input

                      const suggestionsDivId = `${qId}_${type}_suggestions`;
                      const suggestionsDiv = document.getElementById(suggestionsDivId);

                       if (!aiEnabled) { showApiConfigWarning(); return; }

                       button.classList.add('loading');
                       button.disabled = true; // Disable while loading

                       if (suggestionsDiv) {
                            suggestionsDiv.style.display = 'block';
                            suggestionsDiv.innerHTML = '<p class="text-center my-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando sugestões...</p>';
                       }

                       const context = buildFullContext();
                       let prompt = "";
                       let expectsJson = false;

                       // --- Replicate Prompt Building logic here or call a function ---
                        switch (type) {
                             case 'titles':
                                 expectsJson = true;
                                 prompt = `Contexto do eBook:\n- Tema: ${context.theme}\n- Problema Resolvido: ${context.problem}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Resultado Esperado: ${context.readerOutcome}\n\nTarefa: Sugira ${count} combinações criativas de Título e Subtítulo.\nFormato da Resposta (JSON Array de Objetos EXATO):\n[\n  {"title": "Título 1", "subtitle": "Subtítulo 1"},\n  {"title": "Título 2", "subtitle": "Subtítulo 2"}\n]`; // Simplified JSON instruction for direct apply
                                 break;
                             case 'chapters':
                                 expectsJson = true;
                                 // Need to determine subtopic count for the prompt - maybe always ask for a standard number like 3?
                                 const standardSubtopicCount = 3; // Or get from AI Assistance Modal setting? Let's use a fixed number for now.
                                 prompt = `Contexto do eBook:\n- Tema: ${context.theme}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Resultado: ${context.readerOutcome}\n${context.mainChapters && context.mainChapters !== "Não definidos" ? `- Capítulos já pensados (ignorar se for para gerar novos):\n${context.mainChapters}\n` : ''}\nTarefa: Crie um esboço com ${count} capítulos e, para cada um, ${standardSubtopicCount} subtópicos.\nFormato da Resposta (JSON Array de Objetos EXATO):\n[\n  { "name": "Nome do Cap 1", "subtopics": ["Subtópico 1.1", "Subtópico 1.2"] },\n  { "name": "Nome do Cap 2", "subtopics": ["Subtópico 2.1"] }\n]`;
                                 break;
                             case 'persona': prompt = `Contexto:\n- Tema: ${context.theme}\n- Persona Inicial: ${context.personaDesc.substring(0,200) || "Nenhuma"}${context.personaDesc.length > 200 ? '...' : ''}\n\nTarefa: Elabore a persona com mais detalhes: Dores, Objetivos, Canais de Info, Objeções, Demografia/Comportamento. Texto corrido, parágrafos.`; break;
                             case 'problem': prompt = `Contexto:\n- Tema: ${context.theme}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n\nTarefa: Sugira ${count} formas de articular o *problema específico* que este eBook resolve. Foque em tangibilidade. Liste cada um em nova linha. Sem marcadores ou números.`; break;
                             case 'painPoint': prompt = `Contexto:\n- Tema: ${context.theme}\n- Persona: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}\n- Problema Central: ${context.problem}\n\nTarefa: Liste ${count} *dores ou necessidades específicas* da persona. Seja específico e emocionalmente relevante. Liste cada um em nova linha. Sem marcadores ou números.`; break;
                             case 'outcome': prompt = `Contexto:\n- Tema: ${context.theme}\n- Problema Resolvido: ${context.problem}\n- Nível Público: ${context.audienceLevel}\n\nTarefa: Sugira ${count} *resultados práticos ou transformações* que o leitor alcançará. Verbos de ação, resultados mensuráveis. Liste cada um em nova linha. Sem marcadores ou números.`; break;
                             case 'extraElements': prompt = `Contexto:\n- Tema: ${context.theme}\n- Nível Público: ${context.audienceLevel}\n- Objetivo: ${context.ebookObjective}\n\nTarefa: Sugira ${count} *elementos adicionais* (além de caps, intro, conclu) que agregariam valor. Ex: glossários, checklists. Liste cada um em nova linha. Sem marcadores ou números.`; break;
                             case 'writingStyle': prompt = `Contexto:\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Tom: ${context.tone || "Não definido"}\n\nTarefa: Sugira ${count} adjetivos ou frases curtas descrevendo o *estilo de redação* ideal. Pense em clareza, ritmo, linguagem. Liste cada um em nova linha. Sem marcadores ou números.`; break;
                             case 'coverConcept': prompt = `Contexto:\n- Título: ${context.title || context.theme}\n- Subtítulo: ${context.subtitle || "Não definido"}\n- Tema: ${context.theme}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}\n\nTarefa: Sugira ${count} *conceitos visuais distintos* para a capa. Descreva elementos, cores, fontes, sentimento. Formato livre, separe conceitos com "---".`; break;
                             case 'marketingChannels': prompt = `Contexto:\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Distribuição: ${context.distributionModel}\n\nTarefa: Liste ${count} *canais de marketing e divulgação* adequados. Priorize onde o público está. Liste cada um em nova linha. Sem marcadores ou números.`; break;
                             case 'launchAction': prompt = `Contexto:\n- Título: ${context.title || context.theme}\n- Distribuição: ${context.distributionModel}\n- Canais Planejados: ${context.marketingChannelsList || 'Não definidos'}\n\nTarefa: Sugira ${count} ideias criativas e concretas para a *principal ação de lançamento*. Pense em eventos, ofertas. Liste cada ideia em nova linha. Sem marcadores ou números.`; break;

                             // subtopicsFromChapters is handled by a separate button specifically for step 4 q1
                             default: console.error("Unknown AI suggestion type:", type);
                         }
                       // --- End Prompt Building ---


                       try {
                            // Call the unified fetch function
                           const responseText = await getGeminiSuggestions({
                                actionType: type,
                                prompt: prompt,
                                context: context,
                                suggestionCount: count,
                                targetElementId: suggestionsDivId // Pass the target div ID
                           }, count, false); // Do NOT return raw text, let getGeminiSuggestions render to the div

                           // The rendering is handled inside getGeminiSuggestions now
                           // We only need to check if an error occurred (null/undefined returned)
                            if (responseText === undefined || responseText === null) {
                                // Error was handled and displayed inside getGeminiSuggestions
                            }


                       } finally {
                           button.classList.remove('loading');
                           button.disabled = !aiEnabled; // Re-enable if AI is still active
                       }
                 });

                 aiButtonContainer.appendChild(aiButton);
                 labelWrapper.appendChild(aiButtonContainer);

                 // Add the suggestion div placeholder below the input
                 const suggestionsDiv = document.createElement('div');
                 suggestionsDiv.id = `${qData.id}_${qData.aiSuggestion.type}_suggestions`;
                 suggestionsDiv.className = 'ai-suggestions-container'; suggestionsDiv.style.display = 'none';
                 formGroup.appendChild(suggestionsDiv);

            } else if (aiEnabled && qData.id === 'step4_q1') {
                 // Special AI button for generating subtopics from the Chapter list (step4_q0)
                const inlineAiButtonContainer = document.createElement('div');
                inlineAiButtonContainer.className = 'inline-ai-button-container';

                const countInputSubtopics = document.createElement('input');
                countInputSubtopics.type = 'number';
                countInputSubtopics.className = 'form-control form-control-sm ai-count-input';
                countInputSubtopics.id = `${qData.id}_inline_ai_count`;
                countInputSubtopics.min = "1"; countInputSubtopics.max = "5";
                countInputSubtopics.value = "3";
                countInputSubtopics.title = "Sub-tópicos por capítulo";
                inlineAiButtonContainer.appendChild(countInputSubtopics);

                const inlineAiButton = document.createElement('button');
                inlineAiButton.type = 'button';
                inlineAiButton.className = 'btn btn-sm btn-outline-info btn-ai-action';
                inlineAiButton.dataset.suggestionType = 'subtopicsFromChapters';
                inlineAiButton.dataset.sourceFieldId = 'step4_q0';
                inlineAiButton.dataset.targetEditorId = 'step4_q1'; // Explicitly target CKEditor field
                inlineAiButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-magic me-1" viewBox="0 0 16 16"><path d="M9.5 2.672a.5.5 0 1 0 1 0V.843a.5.5 0 0 0-1 0v1.829Zm4.5.035A.5.5 0 0 0 13.293 2L12 3.293a.5.5 0 1 0 .707.707L14 2.707ZM7.293 4A.5.5 0 1 0 8 3.293L6.707 2A.5.5 0 0 0 6 2.707L7.293 4Zm-.621 2.5a.5.5 0 0 0 0-1H4.843a.5.5 0 1 0 0 1h1.829Zm8.485 0a.5.5 0 1 0 0-1h-1.829a.5.5 0 0 0 0 1h1.829ZM13.293 12A.5.5 0 0 0 14 11.293L12.707 10a.5.5 0 1 0-.707.707L13.293 12Zm-5.786 1.328a.5.5 0 1 0-1 0v1.829a.5.5 0 0 0 1 0V13.33Zm-4.5.035a.5.5 0 0 0 .707.707L4 12.707a.5.5 0 0 0-.707-.707L2 13.293a.5.5 0 0 0 .707.707ZM11.5 6.5a.5.5 0 0 0-1 0V8.33a.5.5 0 0 0 1 0V6.5Zm-6.932 1.432a.5.5 0 0 0-.52.045L2.509 9.417a.5.5 0 0 0 .487.878l1.537-1.025a.5.5 0 0 0 .045-.52ZM12.94 9.417l1.538 1.025a.5.5 0 0 0 .487-.878l-1.537-1.025a.5.5 0 0 0-.52-.045L10.5 9.831l.03-.055a.5.5 0 0 0-.03.055l2.44 1.626Z"/></svg>
                    <span>Gerar Sub-tópicos com IA</span>
                    <span class="spinner-border spinner-border-sm ms-2 align-middle"></span>`;

                // Add event listener directly during render
                 inlineAiButton.addEventListener('click', async (event) => {
                      const button = event.currentTarget;
                      const type = button.dataset.suggestionType;
                      const sourceId = button.dataset.sourceFieldId;
                      const targetId = button.dataset.targetEditorId;
                      const cntInput = button.parentElement.querySelector('.ai-count-input');
                      const count = cntInput ? parseInt(cntInput.value) : 3;

                      const chapterListText = document.getElementById(sourceId)?.value?.trim();
                      if (!chapterListText) {
                          Swal.fire('Atenção', 'Por favor, preencha a lista de capítulos principais (campo anterior) antes de gerar os sub-tópicos.', 'info');
                          return;
                      }
                       if (!aiEnabled) { showApiConfigWarning(); return; }

                       button.classList.add('loading');
                       button.disabled = true;

                       const context = buildFullContext();
                       const prompt = `Contexto do eBook:\n- Tema Principal: ${context.theme}\n- Público-Alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Resultado Esperado: ${context.readerOutcome}\n\nCapítulos Principais Fornecidos:\n${chapterListText}\n\nTarefa: Para CADA UM dos capítulos principais fornecidos acima, detalhe ${count} sub-tópicos relevantes e específicos. Mantenha a progressão lógica.\n\nFormato da Resposta (Use este formato EXATO para cada capítulo fornecido, sem JSON wrapper, apenas o texto):\n[Título do Capítulo Fornecido 1]\n  - Subtópico 1.1\n  - Subtópico 1.2\n\n[Título do Capítulo Fornecido 2]\n  - Subtópico 2.1\n  - Subtópico 2.2\n... (e assim por diante para todos os capítulos fornecidos)`;


                       try {
                           // Call the unified fetch function
                           await getGeminiSuggestions({
                                actionType: type,
                                prompt: prompt, // Pass the pre-built prompt
                                context: context, // Pass context if needed server-side
                                suggestionCount: count, // Suggestion count is per chapter for subtopics
                                directTargetFieldId: targetId, // Indicate direct field update
                                feedbackAnchorElement: button // Pass button to show feedback near it
                           }, count, false); // Do NOT return raw text, let getGeminiSuggestions handle direct update

                       } finally {
                            button.classList.remove('loading');
                            button.disabled = !aiEnabled;
                       }
                 });

                formGroup.appendChild(inlineAiButtonContainer);
            }


            let inputElement;
            switch (qData.type) {
                case 'textarea':
                    inputElement = document.createElement('textarea');
                    inputElement.className = 'form-control';
                    inputElement.rows = qData.rows || 3;
                    if (qData.placeholder) inputElement.placeholder = qData.placeholder;
                    break;
                case 'text':
                    inputElement = document.createElement('input'); inputElement.type = 'text';
                    inputElement.className = 'form-control';
                    if (qData.placeholder) inputElement.placeholder = qData.placeholder;
                    break;
                case 'select':
                    inputElement = document.createElement('select'); inputElement.className = 'form-select';
                    // Add default empty option if not present in data
                    if (!qData.options.find(opt => opt.value === "")) {
                         const defOpt = document.createElement('option'); defOpt.value = ""; defOpt.textContent = "-- Selecione --";
                         // Check if the current collected value is empty or null before selecting default
                         if (!collectedFormData[qData.id]) defOpt.selected = true;
                         inputElement.appendChild(defOpt);
                    }
                    qData.options.forEach(optData => {
                        const opt = document.createElement('option'); opt.value = optData.value; opt.textContent = optData.label; inputElement.appendChild(opt);
                    });
                    break;
                 case 'radio': case 'checkbox':
                    const choiceContainer = document.createElement('div'); // Create a container for radio/checkbox groups
                    choiceContainer.id = qData.id; // Assign the question ID to the container
                    qData.options.forEach((optData, optIdx) => {
                        const wrap = document.createElement('div'); wrap.className = 'form-check';
                        const inp = document.createElement('input'); inp.type = qData.type; inp.className = 'form-check-input';
                        inp.name = qData.type === 'radio' ? qData.id : `${qData.id}_${optData.value}`; // Use unique names for checkboxes
                        inp.id = `${qData.id}_${optData.value}_opt`; // Unique ID for label
                        inp.value = optData.value;
                        const optLabel = document.createElement('label'); optLabel.className = 'form-check-label';
                        optLabel.htmlFor = inp.id; optLabel.textContent = optData.label;
                        wrap.appendChild(inp); wrap.appendChild(optLabel); choiceContainer.appendChild(wrap);
                    });
                    if (qData.otherOption) {
                        const otherWrap = document.createElement('div'); otherWrap.className = 'form-check';
                        const otherInp = document.createElement('input'); otherInp.type = qData.type;
                        otherInp.className = 'form-check-input other-option-trigger';
                        otherInp.name = qData.type === 'radio' ? qData.id : `${qData.id}_other`; // Unique name for 'other' checkbox
                        otherInp.id = `${qData.id}_other_trigger`; otherInp.value = 'other';
                        const otherLabel = document.createElement('label'); otherLabel.className = 'form-check-label';
                        otherLabel.htmlFor = otherInp.id; otherLabel.textContent = 'Outro:';
                        const otherTextInp = document.createElement('input'); otherTextInp.type = 'text';
                        otherTextInp.className = 'form-control other-text-input';
                        otherTextInp.id = `${qData.id}_other_text`; otherTextInp.name = `${qData.id}_other_text`; // Unique name for other text input
                        otherTextInp.placeholder = 'Por favor, especifique'; otherTextInp.style.display = 'none'; // Initially hidden
                        otherWrap.appendChild(otherInp); otherWrap.appendChild(otherLabel); otherWrap.appendChild(otherTextInp);
                        choiceContainer.appendChild(otherWrap);
                    }
                    formGroup.appendChild(choiceContainer); // Add the container to the form group
                    break;
                default:
                    // Fallback for unknown types
                    inputElement = document.createElement('input'); inputElement.type = 'text';
                    inputElement.className = 'form-control';
                    if (qData.placeholder) inputElement.placeholder = qData.placeholder;
                    console.warn(`Unknown input type: ${qData.type} for question ${qData.id}. Using text input.`);
            }


            // Add the input element to the form group if it exists
            if (inputElement && qData.type !== 'radio' && qData.type !== 'checkbox') {
                inputElement.id = qData.id; inputElement.name = qData.id;
                if (qData.required) { inputElement.required = true; inputElement.setAttribute('aria-required', 'true'); }
                // Set value for text/textarea/select BEFORE CKEditor might take over
                if (collectedFormData.hasOwnProperty(qData.id) && !ckEditorFieldIds.includes(qData.id)) {
                    inputElement.value = collectedFormData[qData.id];
                }
                formGroup.appendChild(inputElement);

                // Initialize CKEditor for specified textareas
                if (qData.type === 'textarea' && ckEditorFieldIds.includes(qData.id)) {
                    inputElement.style.display = 'none'; // Hide the original textarea
                    const wrapperDiv = document.createElement('div');
                    wrapperDiv.classList.add('ckeditor-wrapper-class'); // Add class for validation styling
                    wrapperDiv.dataset.targetValidationId = qData.id; // Store the ID for validation
                    formGroup.insertBefore(wrapperDiv, inputElement.nextSibling); // Insert wrapper after textarea
                    wrapperDiv.appendChild(inputElement); // Move textarea into wrapper

                    try {
                        // Use ClassicEditor from the CDN
                        const editor = await ClassicEditor.create(inputElement, {
                            toolbar: { items: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo' ], shouldNotGroupWhenFull: true },
                            language: 'pt-br',
                             // Pass collected data to editor if it exists
                             initialData: collectedFormData[qData.id] || '',
                             placeholder: qData.placeholder || '' // Set placeholder
                        });
                        ckEditorInstances[qData.id] = editor; // Store the editor instance

                        // Listen for data changes to schedule auto-save
                        editor.model.document.on('change:data', scheduleAutoSave);

                        // --- Inline AI Button Logic for this CKEditor instance ---
                        if(aiEnabled) {
                            // Listen for selection changes to show/hide the inline AI button
                            editor.model.document.selection.on('change:range', () => {
                                clearTimeout(debounceTimerInlineButton);
                                debounceTimerInlineButton = setTimeout(() => {
                                     // Only show if selection is not collapsed and editor is focused
                                    if (!editor.model.document.selection.isCollapsed && editor.editing.view.state === 'focused') {
                                        showInlineAiButton(editor);
                                    } else {
                                        hideInlineAiButton();
                                    }
                                }, 200); // Debounce to avoid rapid show/hide
                            });
                             // Listen for focus/blur to handle edge cases
                            editor.editing.view.on('focus', () => {
                                 if (!editor.model.document.selection.isCollapsed) {
                                     showInlineAiButton(editor);
                                 }
                             });
                             editor.editing.view.on('blur', () => {
                                 // Delay hide to allow clicking the floating button
                                 setTimeout(() => {
                                     // Check if the mouse is over the button or the dropdown is open
                                     if (!inlineAiFloatingButton.matches(':hover') && !inlineAiFloatingButton.querySelector('.dropdown-menu.show')) {
                                        hideInlineAiButton();
                                     }
                                 }, 150);
                             });
                        }
                        // --- End of Inline AI Button Logic ---

                    } catch (error) {
                        console.error(`Error initializing CKEditor for ${qData.id}:`, error);
                        inputElement.style.display = 'block'; // Show the original textarea if CKEditor fails
                        wrapperDiv.remove(); // Remove the empty wrapper
                         // Add a warning/error message near the textarea if CKEditor failed
                         const ckeditorError = document.createElement('div');
                         ckeditorError.className = 'alert alert-warning mt-2';
                         ckeditorError.textContent = `Erro ao carregar editor de texto. Usando campo simples.`;
                         formGroup.appendChild(ckeditorError);
                    }
                }
            } else if (qData.type === 'radio' || qData.type === 'checkbox') {
                 // After adding the choiceContainer, populate it with saved data
                 const container = formGroup.querySelector(`#${qData.id}`);
                 if (container) {
                     if (qData.type === 'radio') {
                         const valToSel = collectedFormData[qData.id];
                         if (valToSel) {
                            const radioToChk = container.querySelector(`input[value="${valToSel}"]`);
                            if (radioToChk) {
                                radioToChk.checked = true;
                                // Manually trigger change for 'other' option to show text input
                                if (valToSel === 'other') radioToChk.dispatchEvent(new Event('change', {bubbles: true}));
                            }
                         }
                     } else { // Checkbox
                         qData.options.forEach(optData => {
                             const chk = container.querySelector(`input[name="${qData.id}_${optData.value}"]`);
                             if (chk && collectedFormData[chk.name] === 'on') chk.checked = true;
                         });
                     }
                     // Handle 'other' option for both radio and checkbox
                     const otherChk = container.querySelector(`#${qData.id}_other_trigger`);
                     const otherTxtInp = container.querySelector(`#${qData.id}_other_text`);
                     if (otherChk && otherTxtInp) {
                          // Determine if 'other' should be checked based on saved data
                          const isOtherCheckedInSavedData =
                              (qData.type === 'radio' && collectedFormData[qData.id] === 'other') ||
                              (qData.type === 'checkbox' && collectedFormData[otherChk.name] === 'on');

                          if (isOtherCheckedInSavedData) {
                                otherChk.checked = true;
                                otherTxtInp.style.display = 'block'; // Show the text input
                                // Set the value from saved data
                                otherTxtInp.value = collectedFormData[otherTxtInp.name] || '';
                                // Set required status based on question definition
                                otherTxtInp.required = qData.required ?? false;
                          } else {
                                // Ensure text input is hidden and not required if 'other' is not checked
                                otherTxtInp.style.display = 'none';
                                otherTxtInp.required = false;
                          }
                          // Attach listeners for 'other' option visibility and required status
                          attachOtherOptionListeners(stepDiv); // Attach listeners to the whole step div
                     }
                 }
            }


            // Add a placeholder div for AI suggestions rendered by AI button (if AI is enabled and button exists)
            if (aiEnabled && qData.aiSuggestion && qData.aiSuggestion.type !== 'subtopicsFromChapters') {
                 const suggestionsDiv = document.createElement('div');
                 suggestionsDiv.id = `${qData.id}_${qData.aiSuggestion.type}_suggestions`;
                 suggestionsDiv.className = 'ai-suggestions-container';
                 suggestionsDiv.style.display = 'none'; // Initially hidden
                 formGroup.appendChild(suggestionsDiv);
            }

            // Add a div for validation feedback (Bootstrap's .invalid-feedback is usually sibling to input)
            // We are using a custom validation message div at the form level, so this might be redundant
            // unless you want specific field-level feedback. Keeping it simple for now.
            // const feedbackDiv = document.createElement('div'); feedbackDiv.className = 'invalid-feedback';
            // feedbackDiv.textContent = 'Este campo é obrigatório.';
            // formGroup.appendChild(feedbackDiv);

            stepDiv.appendChild(formGroup);
        } // End of for...of loop through questions

        stepsContainer.appendChild(stepDiv);
        progressIndicator.textContent = `Etapa ${index + 1} de ${steps.length}`;
        prevBtn.disabled = index === 0;
        nextBtn.textContent = index === steps.length - 1 ? 'Finalizar Planejamento' : 'Próximo';
        nextBtn.className = index === steps.length - 1 ? 'btn btn-success' : 'btn btn-primary'; // Use Bootstrap class

        // Initialize tooltips after adding elements to DOM
        const tooltipTriggerList = stepDiv.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

        // Attempt to focus the first input element
        const firstVisibleInput = stepDiv.querySelector('input:not([type="hidden"]):not([type="radio"]):not([type="checkbox"]):not(.other-text-input):not([style*="display: none"]), select, textarea:not([style*="display: none"])');
         // Prioritize CKEditor if available
        const firstCkId = step.questions.find(q => ckEditorFieldIds.includes(q.id))?.id;
        if (firstCkId && ckEditorInstances[firstCkId]) {
            setTimeout(() => {
                try {
                    ckEditorInstances[firstCkId].editing.view.focus();
                } catch (e) { console.warn("CKEditor focus error", e); }
            }, 150); // Small delay to ensure editor is ready
        } else if (firstVisibleInput) {
             setTimeout(() => firstVisibleInput.focus(), 50); // Small delay to ensure element is ready
        } else {
             // Fallback: try to focus the 'other' text input if it's visible and first
             const firstOtherTextInput = stepDiv.querySelector('.other-text-input:not([style*="display: none"])');
             if(firstOtherTextInput) {
                 setTimeout(() => firstOtherTextInput.focus(), 50);
             }
         }


    }

    // attachOtherOptionListeners function remains the same
    function attachOtherOptionListeners(stepElement) {
        // Find triggers that are not already handled
        const otherTriggers = stepElement.querySelectorAll('.other-option-trigger:not([data-listener-attached])');
        otherTriggers.forEach(trigger => {
            const textInputId = trigger.id.replace('_trigger', '_text');
            const textInput = stepElement.querySelector(`#${textInputId}`);
            if (!textInput) return;

            // Determine the question ID based on the trigger's name
            const questionIdForRequired = trigger.type === 'radio' ? trigger.name : trigger.name.replace('_other', '');
            // Find the question definition to check if it's required
            const stepDefinition = steps.find(s => s.questions.some(q => q.id === questionIdForRequired));
            const questionDefinition = stepDefinition?.questions.find(q => q.id === questionIdForRequired);


            const handleTriggerChange = (event) => {
                // If this trigger is checked, show its text input and make it required if the overall question is required
                if (event.target.checked) {
                    textInput.style.display = 'block';
                    textInput.required = questionDefinition?.required ?? false;
                } else {
                    // If unchecked (only applicable for checkboxes), hide and clear
                    if (trigger.type === 'checkbox') {
                        textInput.style.display = 'none'; textInput.value = '';
                        textInput.required = false; textInput.classList.remove('is-invalid');
                    }
                }
                // Important: Schedule save after state change
                scheduleAutoSave();
            };
            trigger.addEventListener('change', handleTriggerChange);

            // For radio buttons, need to listen to other radios in the group
            if (trigger.type === 'radio') {
                const radioGroup = stepElement.querySelectorAll(`input[type="radio"][name="${trigger.name}"]`);
                radioGroup.forEach(radio => {
                    // Add listener to other radios that are *not* the 'other' trigger itself
                    if (radio !== trigger) {
                        radio.addEventListener('change', () => {
                            // If a non-'other' radio is checked, hide the 'other' text input and make it not required
                            if (radio.checked) {
                                textInput.style.display = 'none'; textInput.value = ''; // Clear value when hiding
                                textInput.required = false; textInput.classList.remove('is-invalid'); // Remove invalid state
                                scheduleAutoSave(); // Schedule save
                            }
                        });
                    }
                });
             }
             // Mark trigger as having listener attached to avoid duplicates on re-render if not fully destroying
             trigger.dataset.listenerAttached = 'true';
             if(textInput) textInput.dataset.listenerAttached = 'true'; // Also mark text input

        });
    }


    function collectStepData() {
         // Find the definition of the current step
        const currentStepDefinition = steps[currentStep];
        if (!currentStepDefinition) return;

        // Find the DOM element for the current step
        const stepElement = stepsContainer.querySelector(`.wizard-step[data-step-index="${currentStep}"]`);
        if (!stepElement) return;

        // Collect data from inputs and selects using FormData
        const form = document.getElementById('wizardForm');
        const formData = new FormData(form);
        const dataFromThisForm = {};

        // Convert FormData to a simple object
        formData.forEach((value, key) => {
            dataFromThisForm[key] = value;
        });

        // Manually collect data from CKEditor instances if they exist for this step's questions
        currentStepDefinition.questions.forEach(qData => {
            const questionId = qData.id;

            if (ckEditorInstances[questionId]) {
                // Get data directly from CKEditor instance
                collectedFormData[questionId] = ckEditorInstances[questionId].getData();
            } else if (qData.type === 'checkbox') {
                 // Collect checkbox states
                qData.options?.forEach(option => {
                    const checkboxName = `${questionId}_${option.value}`;
                     // Check if the checkbox element exists and is checked in the DOM
                    const checkboxElement = stepElement.querySelector(`input[name="${checkboxName}"]`);
                    if (checkboxElement && checkboxElement.checked) {
                        collectedFormData[checkboxName] = 'on';
                    } else {
                        // Remove from collectedFormData if not checked (important for persistence)
                        delete collectedFormData[checkboxName];
                    }
                });
                // Handle the 'other' checkbox and its text input
                if (qData.otherOption) {
                    const otherCheckboxName = `${questionId}_other`;
                    const otherTextName = `${questionId}_other_text`;
                     const otherCheckboxElement = stepElement.querySelector(`input[name="${otherCheckboxName}"]`);
                     const otherTextElement = stepElement.querySelector(`input[name="${otherTextName}"]`);

                    if (otherCheckboxElement && otherCheckboxElement.checked) {
                        collectedFormData[otherCheckboxName] = 'on';
                        collectedFormData[otherTextName] = otherTextElement ? otherTextElement.value || '' : '';
                    } else {
                         // Remove from collectedFormData if not checked
                        delete collectedFormData[otherCheckboxName];
                        delete collectedFormData[otherTextName];
                    }
                }
            } else if (qData.type === 'radio') {
                 // Collect selected radio value
                const radioElements = stepElement.querySelectorAll(`input[name="${questionId}"]`);
                let selectedValue = null;
                radioElements.forEach(radio => {
                    if (radio.checked) selectedValue = radio.value;
                });

                if (selectedValue !== null) {
                    collectedFormData[questionId] = selectedValue;
                     // If 'other' is selected, also collect the text value
                    if (selectedValue === 'other' && qData.otherOption) {
                        const otherTextName = `${questionId}_other_text`;
                        const otherTextElement = stepElement.querySelector(`input[name="${otherTextName}"]`);
                         collectedFormData[otherTextName] = otherTextElement ? otherTextElement.value || '' : '';
                    } else if (qData.otherOption) {
                         // Remove other text if a non-'other' option is selected
                         delete collectedFormData[`${questionId}_other_text`];
                    }
                } else {
                     // If no radio is checked (shouldn't happen if required, but for safety)
                    delete collectedFormData[questionId];
                     if (qData.otherOption) delete collectedFormData[`${questionId}_other_text`];
                }

            } else {
                 // For simple text/textarea/select inputs
                const inputElement = stepElement.querySelector(`#${questionId}`);
                if (inputElement) {
                    collectedFormData[questionId] = inputElement.value;
                } else {
                    // If the element wasn't found (e.g., type mismatch or error), ensure it's removed or set to empty in state
                    // unless it's a field that might exist from a template but not rendered on this step.
                    // A safer approach might be to only update fields present in the current step's DOM.
                    // Let's stick to updating if found in DOM, otherwise leave previous state or remove if known type.
                    // For now, if element not found in DOM, do nothing to collectedFormData for this ID.
                     // If you want to explicitly clear, uncomment: delete collectedFormData[questionId];
                }
            }
        });
         console.log("Collected form data:", collectedFormData);
    }


    // --- Report Generation Functions ---
    function getAnswerForQuestion(qData, data) {
        let answer = { text: null, list: null, isEmpty: true, isHtml: false };
        const value = data[qData.id];

        if (ckEditorFieldIds.includes(qData.id)) {
            const editorContent = data[qData.id] || "";
             // Check if HTML content is effectively empty (only whitespace or <p></p>)
             const isEmptyHtml = editorContent.replace(/<[^>]*>/g, '').trim() === '';

            if (!isEmptyHtml) {
                answer.text = editorContent;
                answer.isEmpty = false;
                answer.isHtml = true;
            }
        } else {
            switch (qData.type) {
                case 'radio': case 'select':
                    if (value) {
                        answer.isEmpty = false;
                        if (value === 'other') {
                            // For radio/select 'other', the text input name is questionId_other_text
                            const otherText = data[`${qData.id}_other_text`];
                            answer.text = otherText ? `Outro: ${otherText}` : 'Outro (não especificado)';
                        } else {
                            const option = qData.options?.find(opt => opt.value === value);
                            answer.text = option ? option.label : value;
                        }
                    }
                    break;
                case 'checkbox':
                    const checkedItems = [];
                    qData.options?.forEach(option => {
                        // For checkboxes, data is stored as questionId_optionValue: 'on'
                        if (data[`${qData.id}_${option.value}`] === 'on') checkedItems.push(option.label);
                    });
                     // Check the 'other' checkbox state and text value
                    if (data[`${qData.id}_other`] === 'on') {
                        const otherText = data[`${qData.id}_other_text`];
                        checkedItems.push(otherText ? `Outro: ${otherText}` : 'Outro (não especificado)');
                    }
                    if (checkedItems.length > 0) { answer.list = checkedItems; answer.isEmpty = false; }
                    break;
                case 'textarea': case 'text': default:
                    const textValue = data[qData.id] || "";
                    if (textValue.trim() !== "") { answer.text = textValue; answer.isEmpty = false; }
                    break;
            }
        }
        return answer;
    }

    function generateReportHTML(data, theme = 'default') {
        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        const safeTitle = escapeHtml(ebookTitle);
        let themeStyles = getThemeStyles(theme);
        let reportHTML = `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>Plano Detalhado: ${safeTitle}</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"><style>${themeStyles} pre { white-space: pre-wrap; word-wrap: break-word; font-family: monospace; background-color: rgba(0,0,0,0.03); padding: 0.5em; border-radius: 4px; } body.dark pre { background-color: rgba(255,255,255,0.1); } body.blueish pre { background-color: rgba(14, 165, 233, 0.1); } .ck-content { padding: 0 !important; margin: 0 !important; border: none !important; } .ck-content h2 { font-size: 1.2em; margin-top: 1em; margin-bottom: 0.5em; } .ck-content ul, .ck-content ol { margin-left: 20px; margin-bottom: 0.5em;} </style></head><body class="${theme}"><div class="container report-render-container"><h1>Plano Detalhado: ${safeTitle}</h1>`;
        steps.forEach((step, stepIndex) => {
            reportHTML += `<h2>${escapeHtml(step.title)}</h2>`;
            step.questions.forEach(qData => {
                reportHTML += `<div class="question-block"><strong class="question-label">${escapeHtml(qData.label)}</strong>`;
                const answer = getAnswerForQuestion(qData, data);
                if (answer.isEmpty) {
                    reportHTML += `<p class="empty-answer">- Não preenchido -</p>`;
                } else if (answer.list) {
                    reportHTML += `<ul class="answer-list">${answer.list.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
                } else if (answer.text) {
                    if (answer.isHtml) {
                        // Assuming CKEditor HTML is relatively clean and safe for embedding
                        reportHTML += `<div class="answer-html ck-content">${answer.text}</div>`;
                    } else {
                        // For plain text, convert newlines to breaks or keep pre-formatted
                         if (answer.text.includes('\n') && answer.text.length > 100) { // Heuristic for code/list blocks
                              reportHTML += `<pre class="answer-text">${escapeHtml(answer.text)}</pre>`;
                         } else {
                              reportHTML += `<p class="answer-text">${escapeHtml(answer.text).replace(/\n/g, '<br>')}</p>`;
                         }
                    }
                }
                reportHTML += `</div>`;
            });
            if (stepIndex < steps.length - 1) reportHTML += `<hr />`;
        });
        reportHTML += `</div></body></html>`;
        return reportHTML;
    }

    function generateReportMarkdown(data) {
        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        let md = `# Plano Detalhado: ${ebookTitle}\n\n`;
        // Initialize TurndownService if available
        const turndownService = typeof TurndownService !== 'undefined' ? new TurndownService({headingStyle: 'atx', codeBlockStyle: 'fenced'}) : null;
        if (!turndownService) console.warn("TurndownService not loaded, HTML content will not be converted to Markdown.");


        steps.forEach((step, stepIndex) => {
            md += `## ${step.title}\n\n`;
            step.questions.forEach(qData => {
                md += `**${qData.label}**\n\n`;
                const answer = getAnswerForQuestion(qData, data);
                if (answer.isEmpty) md += `*Não preenchido*\n\n`;
                else if (answer.list) md += answer.list.map(item => `- ${item}\n`).join('') + `\n`;
                else if (answer.text) {
                    let textToUse = answer.text;
                    if (answer.isHtml && turndownService) {
                        try {
                            textToUse = turndownService.turndown(answer.text);
                        } catch (e) {
                            console.warn("Falha ao converter HTML para Markdown, usando texto simples:", e);
                            textToUse = stripHtml(answer.text);
                        }
                    } else if (answer.isHtml) {
                        // If TurndownService not available, fallback to stripping HTML
                        textToUse = stripHtml(answer.text);
                    }
                    // Format plain text with code block if it contains newlines
                    if (textToUse.includes('\n') && !textToUse.startsWith('```') && !textToUse.includes('\n```\n')) {
                         md += "```text\n" + textToUse + "\n```\n\n"; // Specify text for better rendering
                    } else {
                         md += `${textToUse}\n\n`;
                    }
                }
            });
            if (stepIndex < steps.length - 1) md += `***\n\n`;
        });
        return md;
    }

    function generateReportText(data) {
        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        let txt = `Plano Detalhado: ${ebookTitle}\n========================================\n\n`;
        steps.forEach((step, stepIndex) => {
            txt += `== ${step.title} ==\n\n`;
            step.questions.forEach(qData => {
                txt += `${qData.label}:\n`;
                const answer = getAnswerForQuestion(qData, data);
                if (answer.isEmpty) txt += `- Não preenchido -\n\n`;
                else if (answer.list) txt += answer.list.map(item => `  * ${item}\n`).join('') + `\n`;
                else if (answer.text) {
                    // Strip HTML for plain text report
                    const textToUse = answer.isHtml ? stripHtml(answer.text) : answer.text;
                    // Indent lines for readability
                    txt += textToUse.split('\n').map(line => `  ${line}`).join('\n') + `\n\n`;
                }
            });
            if (stepIndex < steps.length - 1) txt += "----------------------------------------\n\n";
        });
        return txt;
    }

     function generateReportJSON(data) {
         const reportData = { title: data['step3_q0'] || "Meu Novo eBook", generatedAt: new Date().toISOString(), plan: {} };
         steps.forEach((step, stepIndex) => {
             // Create a clean key from the step title
             const stepKey = `step_${stepIndex + 1}_${step.title.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim().replace(/\s+/g, '_')}`;
             reportData.plan[stepKey] = { title: step.title, questions: {} };
             step.questions.forEach(qData => {
                 const answer = getAnswerForQuestion(qData, data);
                 let value = null;
                 if (!answer.isEmpty) {
                    if (answer.list) value = answer.list;
                    else if (answer.isHtml) value = answer.text;
                    else value = answer.text;
                 }
                 reportData.plan[stepKey].questions[qData.id] = { label: qData.label, answer: value, isHtml: answer.isHtml && !answer.list };
             });
         });
         return JSON.stringify(reportData, null, 2);
     }

    function downloadFile(content, filename, contentType) {
        // Use FileSaver.js
        const blob = new Blob([content], { type: `${contentType};charset=utf-8` });
        saveAs(blob, filename);
    }

    function getFilename(baseName, extension) {
        // Generate a safe filename from the eBook title
        const titlePart = (collectedFormData['step3_q0'] || baseName).normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9\s-]/gi, '').trim().replace(/\s+/g, '-').toLowerCase();
        // Ensure filename is not empty
        return `${titlePart || baseName}.${extension}`;
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
         // Basic HTML escaping
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function getThemeStyles(theme) {
        // Basic theme styles - you can expand this CSS
        let styles = `
            body { font-family: 'Inter', sans-serif; line-height: 1.6; margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; }
            .container.report-render-container { max-width: 800px; margin: 2rem auto; padding: 2rem 3rem; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); transition: background-color 0.3s, border-color 0.3s; }
            h1 { text-align: center; margin-bottom: 1.5rem; font-weight: 600; }
            h2 { border-bottom: 2px solid; padding-bottom: 0.5rem; margin-top: 2.5rem; margin-bottom: 1.5rem; font-size: 1.6rem; font-weight: 600; transition: color 0.3s, border-color 0.3s; }
            .question-block { margin-bottom: 2rem; }
            .question-label { display: block; margin-bottom: 0.5rem; font-size: 1.1rem; font-weight: 600; transition: color 0.3s; }
            .answer-text, .answer-html, .answer-list, pre.answer-text { margin-bottom: 1rem; padding-left: 1em; transition: color 0.3s; }
            .answer-text, pre.answer-text { white-space: pre-wrap; }
            .answer-html p:first-child { margin-top: 0; } .answer-html p:last-child { margin-bottom: 0;}
            .answer-list { list-style: disc; padding-left: 2.5em; margin-top: 0.5rem; }
            .answer-list li { margin-bottom: 0.3rem; }
            hr { border: 0; height: 1px; margin: 3rem 0; transition: background-color 0.3s; }
            .empty-answer { font-style: italic; padding-left: 1em; transition: color 0.3s; }
        `;
        // Print-specific styles to remove box-shadow/margins
        styles += `@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .container.report-render-container { box-shadow: none; border: none; margin: 0; padding:0; max-width: 100%;} } `;
         // Animation for inline AI feedback flash
         styles += `.ai-content-updated-flash { animation: flash-background 1s ease-out; } @keyframes flash-background { 0% { background-color: yellow; }