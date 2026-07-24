<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Card Components Demo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; color: #111827; margin: 0; padding: 2rem; }
        .section { margin-bottom: 2.5rem; }
        h2 { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="section">
        <h2>List Layout</h2>
        <x-book-card-list :books="$books" />
    </div>

    <div class="section">
        <h2>Grid Layout</h2>
        <x-book-card-grid :books="$books" />
    </div>
</body>
</html>
