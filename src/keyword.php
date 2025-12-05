<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">

        <title>CVE Analyzer - Keyword Analysis</title>
        <link rel="icon" href="assets/icons/favicon-64.svg" type="image/svg+xml">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <link rel="stylesheet" href="styles/style.css">
        <link rel="stylesheet" href="styles/keyword.css">
    </head>
    <body>
        <header class="header">
            <div class="container text-center">
                <h1>CVEs Lookup</h1>
                <a href="index.php" class="btn btn-outline-primary mt-2">
                    <i class="bi bi-arrow-left"></i> New search
                </a>
            </div>
        </header>

        <main class="main-content">
            <div class="container-fluid">
                <div class="row justify-content-center mb-4">
                    <div class="col-12 col-lg-8">
                        <div class="card shadow">
                            <div class="card-body">
                                <form id="keywordForm">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label for="keyword" class="form-label">Mot-clé (Vendor/Product)</label>
                                            <input type="text" class="form-control" id="keyword" name="keyword"
                                                   placeholder="e.g., Oracle, Microsoft, Apache..." required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="timeRange" class="form-label">Période</label>
                                            <select class="form-select" id="timeRange" name="time_range">
                                                <option value="30">1 mois</option>
                                                <option value="90" selected>3 mois</option>
                                                <option value="180">6 mois</option>
                                                <option value="365">1 an</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-search"></i> Analyser
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="resultsSection" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card stat-card danger">
                                <div class="value" id="avgCVSS">-</div>
                                <div class="label">Score CVSS Moyen</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card stat-card warning">
                                <div class="value" id="avgEPSS">-</div>
                                <div class="label">Score EPSS Moyen</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card stat-card info">
                                <div class="value" id="kevCount">-</div>
                                <div class="label">CVE dans KEV</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card stat-card success">
                                <div class="value" id="totalCVE">-</div>
                                <div class="label">Total CVE trouvées</div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Stats -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-6">
                            <div class="card stat-card">
                                <div class="value" id="avgSeverity">-</div>
                                <div class="label">Sévérité Moyenne</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card stat-card">
                                <div class="value" id="topCWE">-</div>
                                <div class="label">CWE la plus fréquente</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">📈 Tendance CVSS sur la période</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="cvssChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">📊 Distribution par Sévérité</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="severityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">🔥 Top 10 CVSS Scores</h5>
                                </div>
                                <div class="card-body" id="topCVSS">
                                    <div class="text-center">
                                        <div class="loading-spinner"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header text-dark">
                                    <h5 class="mb-0">💥 Top 10 EPSS Scores</h5>
                                </div>
                                <div class="card-body" id="topEPSS">
                                    <div class="text-center">
                                        <div class="loading-spinner"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <div class="modal fade" id="cveModal" tabindex="-1" aria-labelledby="cveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="cveModalLabel">CVE Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="cveModalBody">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a id="cveModalNvdLink" href="#" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i> View on NVD
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script src="js/keyword.js"></script>
    </body>
</html>
