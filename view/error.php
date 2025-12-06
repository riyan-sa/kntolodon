<?php require __DIR__ . '/components/head.php'; ?>
<title>404 - Halaman Tidak Ditemukan</title>
</head>
<body class="bg-gray-100 h-full mx-auto px-4 py-8">
    <main class="flex flex-col items-center justify-center">
        <div class="bg-white shadow-lg rounded-lg p-8 text-center max-w-md">
            <h1 class="text-4xl font-bold text-red-500 mb-4">404</h1>
            <p class="text-lg text-gray-700 mb-6">Halaman tidak ditemukan.</p>
            <a href="index.php?page=home" class="inline-block px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Kembali ke Awal</a>
        </div>
    </main>
    <?php require __DIR__ . '/components/Footer.php'; ?>
</body>
</html>
