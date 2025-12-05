<!doctype html>
<html lang="fr">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Recherche CVE - Résultats</title>
      <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
      <link rel="stylesheet" href="styles/style.css">
      <link rel="stylesheet" href="styles/loader.css">
      <link rel="stylesheet" href="styles/results.css">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <img src="assets/favicon.svg" alt="logo" width="30" height="24">
                </a>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="index.php">ID CVE</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="keyword.php">Rechercher</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

      <main class="main-content container mt-5">
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
