<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(defined('site_description') ? constant('site_description') : 'Layanan travel terpercaya untuk perjalanan Anda'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(defined('site_description') ? constant('site_description') : 'Layanan travel terpercaya untuk perjalanan Anda'); ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>">
    <meta property="og:type" content="website">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
        }
        .btn-primary {
            background-color: #2563eb;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            transition-duration: 300ms;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            background-color: #4b5563;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            transition-duration: 300ms;
        }
        .btn-secondary:hover {
            background-color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
        }
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #3b82f6;
            border-color: transparent;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
        }
        .alert-warning {
            background-color: #fffbeb;
            color: #92400e;
        }
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php if (isset($_SESSION['flash'])): ?>
        <?php foreach ($_SESSION['flash'] as $type => $message): ?>
            <div class="alert alert-<?php echo $type; ?> text-center">
                <?php echo $message; ?>
            </div>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
