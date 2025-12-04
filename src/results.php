<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Résultats CVE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <header class="header">
    <div class="container text-center">
      <h1>Résultats CVE</h1>
      <a href="index.php" class="btn btn-outline-primary mt-2">
        <i class="bi bi-arrow-left"></i> Nouvelle recherche
      </a>
    </div>
  </header>
  <main class="main-content">
    <div class="container">
      <div id="results" class="row g-4"></div>
    </div>
  </main>
  <script>
    const params = new URLSearchParams(window.location.search);
    const cveIds = params.get('cveIds') ? params.get('cveIds').split(',') : [];
    const resultsDiv = document.getElementById('results');

    async function fetchCve(cveId) {
      const url = `https://services.nvd.nist.gov/rest/json/cves/2.0?cveId=${encodeURIComponent(cveId)}`;
      const resp = await fetch(url);
      if (!resp.ok) return null;
      const data = await resp.json();
      return data.vulnerabilities && data.vulnerabilities.length > 0 ? data.vulnerabilities[0] : null;
    }

    function getSeverityBadge(severity) {
      const colors = {
        'CRITICAL': 'danger',
        'HIGH': 'warning',
        'MEDIUM': 'info',
        'LOW': 'secondary'
      };
      return `<span class="badge bg-${colors[severity] || 'secondary'}">${severity || 'N/A'}</span>`;
    }

    function buildMetricsChart(cvss) {
      if (!cvss) return '';
      const score = cvss.baseScore || 0;
      const impact = cvss.impactScore || 0;
      const exploitability = cvss.exploitabilityScore || 0;
      
      return `
        <div class="metrics-visual mt-3">
          <div class="metric-bar mb-2">
            <div class="d-flex justify-content-between mb-1">
              <small><strong>CVSS Score</strong></small>
              <small><strong>${score}/10</strong></small>
            </div>
            <div class="progress" style="height: 20px;">
              <div class="progress-bar bg-danger" style="width: ${score * 10}%"></div>
            </div>
          </div>
          <div class="metric-bar mb-2">
            <div class="d-flex justify-content-between mb-1">
              <small><strong>Impact</strong></small>
              <small><strong>${impact.toFixed(1)}/10</strong></small>
            </div>
            <div class="progress" style="height: 15px;">
              <div class="progress-bar bg-warning" style="width: ${impact * 10}%"></div>
            </div>
          </div>
          <div class="metric-bar">
            <div class="d-flex justify-content-between mb-1">
              <small><strong>Exploitabilité</strong></small>
              <small><strong>${exploitability.toFixed(1)}/10</strong></small>
            </div>
            <div class="progress" style="height: 15px;">
              <div class="progress-bar bg-info" style="width: ${exploitability * 10}%"></div>
            </div>
          </div>
        </div>
      `;
    }

    function buildCVSSDetails(cvss) {
      if (!cvss) return '';
      return `
        <div class="cvss-details mt-2">
          <div class="row g-2 small">
            <div class="col-6"><i class="bi bi-shield-check"></i> AV: ${cvss.attackVector || 'N/A'}</div>
            <div class="col-6"><i class="bi bi-gear"></i> AC: ${cvss.attackComplexity || 'N/A'}</div>
            <div class="col-6"><i class="bi bi-person"></i> PR: ${cvss.privilegesRequired || 'N/A'}</div>
            <div class="col-6"><i class="bi bi-cursor"></i> UI: ${cvss.userInteraction || 'N/A'}</div>
            <div class="col-4"><i class="bi bi-lock"></i> C: ${cvss.confidentialityImpact || 'N/A'}</div>
            <div class="col-4"><i class="bi bi-shield-lock"></i> I: ${cvss.integrityImpact || 'N/A'}</div>
            <div class="col-4"><i class="bi bi-x-circle"></i> A: ${cvss.availabilityImpact || 'N/A'}</div>
          </div>
        </div>
      `;
    }

    function buildCWESection(weaknesses) {
      if (!weaknesses || weaknesses.length === 0) return '<p class="text-muted small">Aucune CWE disponible</p>';
      const cweList = weaknesses.flatMap(w => w.description || [])
        .map(d => {
          const cweId = d.value.match(/CWE-(\d+)/)?.[1];
          if (cweId) {
            return `<a href="https://cwe.mitre.org/data/definitions/${cweId}.html" target="_blank" class="badge bg-light text-dark border me-1 text-decoration-none cwe-badge">${d.value}</a>`;
          }
          return `<span class="badge bg-light text-dark border me-1">${d.value}</span>`;
        })
        .join('');
      return `<div class="cwe-section mt-2">${cweList}</div>`;
    }

    function buildKEVAlert(cve) {
      if (cve.cisaExploitAdd) {
        return `
          <div class="alert alert-danger d-flex align-items-center mt-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
              <strong>⚠️ Exploit actif (KEV)</strong><br>
              <small>Ajouté le ${new Date(cve.cisaExploitAdd).toLocaleDateString()}</small><br>
              <small><strong>Action:</strong> ${cve.cisaRequiredAction || 'N/A'}</small>
            </div>
          </div>
        `;
      }
      return '';
    }

    function buildAffectedTech(configurations) {
      if (!configurations || configurations.length === 0) return '<p class="text-muted small">Aucune technologie affectée disponible</p>';
      const cpes = [];
      configurations.forEach(config => {
        config.nodes?.forEach(node => {
          node.cpeMatch?.forEach(match => {
            if (match.vulnerable && match.criteria) {
              const parts = match.criteria.split(':');
              if (parts.length >= 5) {
                cpes.push(`${parts[3]}:${parts[4]} ${parts[5] !== '*' ? parts[5] : ''}`);
              }
            }
          });
        });
      });
      const uniqueCpes = [...new Set(cpes)].slice(0, 10);
      return `
        <div class="affected-tech mt-2">
          <h6 class="text-muted">Technologies affectées:</h6>
          <div class="d-flex flex-wrap gap-1">
            ${uniqueCpes.map(cpe => `<span class="badge bg-secondary">${cpe}</span>`).join('')}
          </div>
        </div>
      `;
    }

    async function showResults() {
      if (cveIds.length === 0) {
        resultsDiv.innerHTML = '<div class="col-12"><div class="alert alert-warning">Aucun identifiant CVE fourni.</div></div>';
        return;
      }
      resultsDiv.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
      const cards = [];
      for (const cveId of cveIds) {
        const vuln = await fetchCve(cveId);
        if (!vuln) {
          cards.push(`
            <div class="col-12 col-lg-6">
              <div class="card border-danger h-100">
                <div class="card-body">
                  <h5 class="card-title text-danger">${cveId}</h5>
                  <p class="card-text">Aucune donnée trouvée pour ce CVE.</p>
                </div>
              </div>
            </div>
          `);
          continue;
        }
        const desc = vuln.cve.descriptions?.find(d => d.lang === 'en')?.value || 'Pas de description disponible';
        const published = vuln.cve.published ? new Date(vuln.cve.published).toLocaleDateString('fr-FR') : 'N/A';
        const cvssV3 = vuln.cve.metrics?.cvssMetricV3?.[0]?.cvssData;
        const cvssV2 = vuln.cve.metrics?.cvssMetricV2?.[0]?.cvssData;
        const cvss = cvssV3 || cvssV2;
        const severity = cvssV3?.baseSeverity || cvssV2?.severity || 'N/A';
        
        cards.push(`
          <div class="col-12 col-lg-6">
            <div class="card h-100 cve-card">
              <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">${vuln.cve.id}</h5>
                ${getSeverityBadge(severity)}
              </div>
              <div class="card-body">
                <p class="card-text">${desc}</p>
                <hr>
                <div class="cve-meta">
                  <p class="mb-2"><strong><i class="bi bi-calendar3"></i> Publié:</strong> ${published}</p>
                  ${buildMetricsChart(cvss)}
                  ${buildCVSSDetails(cvss)}
                  <hr>
                  <h6 class="mt-3"><i class="bi bi-bug"></i> Faiblesses (CWE):</h6>
                  ${buildCWESection(vuln.cve.weaknesses)}
                  ${buildKEVAlert(vuln.cve)}
                  ${buildAffectedTech(vuln.cve.configurations)}
                </div>
              </div>
              <div class="card-footer bg-light">
                <a href="https://nvd.nist.gov/vuln/detail/${vuln.cve.id}" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-box-arrow-up-right"></i> Voir sur NVD
                </a>
              </div>
            </div>
          </div>
        `);
      }
      resultsDiv.innerHTML = cards.join('');
    }

    showResults();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
