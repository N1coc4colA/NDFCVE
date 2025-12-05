const params = new URLSearchParams(window.location.search);
const cveIds = params.get('cveIds') ? params.get('cveIds').split(',') : [];
const resultsDiv = document.getElementById('results');
const vulnStore = {};

async function checkKevExists(cveId) {
    try {
        const resp = await fetch(`https://kevin.gtfkd.com/kev/exists?cve=${encodeURIComponent(cveId)}`);
        if (!resp.ok) return false;
        const data = await resp.json();
        return data.exists === true;
    } catch {
        return false;
    }
}

async function showResults() {
    if (cveIds.length === 0) {
        resultsDiv.innerHTML = '<div class="col-12"><div class="alert alert-warning">No CVE identifier provided.</div></div>';
        return;
    }

    // Show loading animation
    showLoadingAnimation("Loading CVE Details...", "Fetching vulnerability information");

    const cards = [];

    let colClass = 'col-12 col-lg-6 col-xl-4';

    for (const cveId of cveIds) {
        const [vuln, kevExists] = await Promise.all([
            fetchCve(cveId),
            checkKevExists(cveId)
        ]);

        let kevData = null;
        if (kevExists) {
            kevData = await fetchKevDetails(cveId);
        }

        if (!vuln) {
            cards.push(`
            <div class="${colClass}">
              <div class="card border-danger h-100">
                <div class="card-body">
                  <h5 class="card-title text-danger">${cveId}</h5>
                  <p class="card-text">No data found for this CVE.</p>
                  ${kevData ? buildKEVAlert(kevData) : ''}
                </div>
                <div class="card-footer bg-light">
                  ${buildPocButton(cveId)}
                </div>
              </div>
            </div>
          `);
            continue;
        }
        const desc = vuln?.cve.descriptions?.find(d => d.lang === 'en')?.value || 'No description available';
        const published = vuln?.cve.published ? new Date(vuln.cve.published).toLocaleDateString('en-US') : 'N/A';
        const cvssV3 = vuln?.cve.metrics?.cvssMetricV31?.[0] || vuln?.cve.metrics?.cvssMetricV3?.[0];
        const cvssV2 = vuln?.cve.metrics?.cvssMetricV2?.[0];
        const cvssData = cvssV3?.cvssData || cvssV2?.cvssData;
        const impactScore = cvssV3?.impactScore || cvssV2?.impactScore || 0;
        const exploitabilityScore = cvssV3?.exploitabilityScore || cvssV2?.exploitabilityScore || 0;
        const severity = cvssData?.baseSeverity || cvssV2?.baseSeverity || 'N/A';
        const cvssWithScores = cvssData ? { ...cvssData, impactScore, exploitabilityScore } : null;

        if (vuln) {
            vulnStore[vuln.cve.id] = { vuln, kevData, kevExists, cvssWithScores, cvssData, desc, published };
        }

        if (!vuln) {
            continue;
        }

        cards.push(`
          <div class="${colClass}">
            <div class="card h-100 cve-card ${kevExists ? 'border-danger' : ''}">
              <div class="card-header ${kevExists ? 'bg-danger' : 'bg-primary'} text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  ${vuln.cve.id}
                  ${kevExists ? '<i class="bi bi-exclamation-triangle-fill ms-2" title="Exploit actif"></i>' : ''}
                </h5>
                ${getSeverityBadge(severity)}
              </div>
              <div class="card-body">
                <p class="text-muted mb-1"><i class="bi bi-calendar3"></i> ${published}</p>
                <p class="card-text">${desc.substring(0, 160)}${desc.length > 160 ? '…' : ''}</p>
              </div>
              <div class="card-footer bg-light d-flex justify-content-between">
                <div>
                  <a href="https://nvd.nist.gov/vuln/detail/${vuln.cve.id}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right"></i> NVD
                  </a>
                  ${buildPocButton(vuln.cve.id)}
                  ${kevExists ? `<a href="https://www.cisa.gov/known-exploited-vulnerabilities-catalog" target="_blank" class="btn btn-sm btn-outline-danger ms-2"><i class="bi bi-shield-exclamation"></i> KEV</a>` : ''}
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary detail-btn" data-cve="${vuln.cve.id}">
                  <i class="bi bi-arrows-fullscreen"></i> Détails
                </button>
              </div>
            </div>
          </div>
        `);
    }
    resultsDiv.innerHTML = cards.join('');

    // Hide loading animation
    hideLoadingAnimation();
}

showResults();