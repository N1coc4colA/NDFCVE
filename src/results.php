<!doctype html>
<html lang="fr">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Résultats CVE</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
      <link rel="stylesheet" href="styles/style.css">
        <link rel="stylesheet" href="styles/loader.css">
        <link rel="stylesheet" href="styles/results.css">
    </head>
    <body>
      <header class="header">
        <div class="container text-center">
          <h1>CVE Results</h1>
          <div class="mt-2">
            <a href="index.php" class="btn btn-outline-primary">
              <i class="bi bi-arrow-left"></i> New search
            </a>
            <a href="keyword.php" class="btn btn-outline-success ms-2">
              <i class="bi bi-search"></i> Keyword Analysis
            </a>
          </div>
        </div>
      </header>
      <main class="main-content container">
          <div id="results" class="row g-4"></div>
      </main>
      <include href="templates/modals.html"/>
    </body>
    <script src="js/script.js"></script>
    <script src="js/loader.js"></script>
    <script src="js/cveinfo.js"></script>
    <script src="js/results.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</html>
