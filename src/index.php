<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>CVE Analyzer - Home</title>
    <link rel="icon" href="assets/icons/favicon-64.svg" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/style.css">
  </head>
  <body>
    <header class="header">
      <div class="container text-center">
        <h1>CVE Analyzer</h1>
      </div>
    </header>

    <main class="main-content">
        <div class="container py-5">
            <div class="row g-4 justify-content-center">
                <div class="col-12 col-md-6">
                    <form id="searchForm" class="card shadow-lg w-100" style="max-width:900px;">
                        <div class="card-body p-5 text-center">
                            <h1 class="display-5 mb-2">CVE Lookup</h1>
                            <h2 class="lead mb-4">Search CVE data quickly</h2>

                            <div class="mb-3">
                                <label for="keywords" class="form-label visually-hidden">Keywords</label>
                                <input type="text" class="form-control form-control-lg cve-input" id="keywords" name="keywords" placeholder="Enter keywords, comma separated">
                            </div>

                            <div class="mb-4">
                                <label for="timeRange" class="form-label visually-hidden">Time range</label>
                                <select class="form-select form-select-lg" id="timeRange" name="time_range">
                                    <option value="30">1 month</option>
                                    <option value="90">3 months</option>
                                    <option value="180">6 months</option>
                                    <option value="365">1 year</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Search</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-12 col-md-6">
                    <form id="cveForm" class="card shadow-lg w-100" style="max-width:900px;">
                        <div class="card-body p-5 text-center">
                            <h1 class="display-5 mb-2">CVE Search</h1>
                            <h2 class="lead mb-4">CVE Identifiers Lookup</h2>
                            <div class="mb-3 text-start">
                                <label for="cveFileInput" class="form-label">Upload CVE file</label>
                                <input type="file" class="form-control" id="cveFileInput" accept=".txt" aria-describedby="cveFileHelp">
                                <div id="cveFileHelp" class="form-text">Upload a text file where each line is a CVE ID (for example: CVE-2023-12345). Existing inputs will be replaced by file contents.</div>
                            </div>
                            <div id="cveInputsContainer">
                                <div class="cve-input-group d-flex align-items-center gap-2">
                                    <input type="text" class="form-control form-control-lg cve-input" placeholder="CVE-YYYY-NNNN" pattern="CVE-\d{4}-\d{4,}">
                                </div>
                            </div>
                            <div class="container text-center">
                                <div class="row align-items-start gap-2">
                                    <button type="button" id="clearCVEsBtn" class="btn btn-secondary btn-lg col" aria-label="Clear CVE list">Clear</button>
                                    <button type="submit" class="btn btn-primary btn-lg col">
                                        Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="js/index.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    </body>
</html>


