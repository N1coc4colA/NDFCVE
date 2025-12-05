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

      <div class="modal fade" id="cveModal" tabindex="-1" aria-labelledby="cveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title" id="cveModalLabel">CVE</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cveModalBody">
              <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer">
              <a id="cveModalNvdLink" href="#" target="_blank" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> NVD</a>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="pocModal" tabindex="-1" aria-labelledby="pocModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header bg-dark text-white">
              <h5 class="modal-title" id="pocModalLabel"><i class="bi bi-github"></i> PoCs GitHub</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pocModalBody">
              <div class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
          </div>
        </div>
      </div>
    </body>
    <script src="js/loader.js"></script>
    <script src="js/cveinfo.js"></script>
    <script src="js/results.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</html>
