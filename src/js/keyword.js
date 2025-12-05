// keyword.js - Keyword-based CVE Analysis

const keywordForm = document.getElementById('keywordForm');
const resultsSection = document.getElementById('resultsSection');

let currentData = {
    vulnerabilities: [],
    keyword: '',
    timeRange: 90
};

let cvssChart = null;
let severityChart = null;

// Form submission handler
keywordForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const keyword = document.getElementById('keyword').value.trim();
    const timeRange = parseInt(document.getElementById('timeRange').value);

    if (!keyword) return;

    currentData.keyword = keyword;
    currentData.timeRange = timeRange;

    await performAnalysis(keyword, timeRange);
});

// Main analysis function
async function performAnalysis(keyword, timeRange) {
    // Show results section
    resultsSection.style.display = 'block';

    // Show loading animation
    showLoadingAnimation();

    // Reset stats
    resetStats();

    try {
        // Fetch CVEs from NVD API
        const vulnerabilities = await fetchCVEsByKeyword(keyword, timeRange);

        if (vulnerabilities.length === 0) {
            hideLoadingAnimation();
            alert('Aucune CVE trouvée pour ce mot-clé et cette période.');
            return;
        }

        currentData.vulnerabilities = vulnerabilities;

        // Enrich with EPSS and KEV data
        await enrichVulnerabilities(vulnerabilities);

        // Hide loading animation
        hideLoadingAnimation();

        // Calculate and display statistics
        calculateStats(vulnerabilities);

        // Display charts
        displayCharts(vulnerabilities);

        // Display top vulnerabilities
        displayTopVulnerabilities(vulnerabilities);

    } catch (error) {
        hideLoadingAnimation();
        console.error('Error during analysis:', error);
        alert('Erreur lors de l\'analyse. Veuillez réessayer.');
    }
}

// Fetch CVEs from NVD API by keyword
async function fetchCVEsByKeyword(keyword, days) {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);

    const startDateStr = startDate.toISOString().split('T')[0] + 'T00:00:00.000';
    const endDateStr = endDate.toISOString().split('T')[0] + 'T23:59:59.999';

    const url = `https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=${encodeURIComponent(keyword)}&pubStartDate=${startDateStr}&pubEndDate=${endDateStr}&resultsPerPage=2000`;

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error('NVD API error');

        const data = await response.json();
        return data.vulnerabilities || [];
    } catch (error) {
        console.error('Error fetching CVEs:', error);
        throw error;
    }
}

// Enrich vulnerabilities with EPSS and KEV data
async function enrichVulnerabilities(vulnerabilities) {
    const batchSize = 10;

    for (let i = 0; i < vulnerabilities.length; i += batchSize) {
        const batch = vulnerabilities.slice(i, i + batchSize);

        await Promise.all(batch.map(async (vuln) => {
            const cveId = vuln.cve.id;

            // Fetch EPSS score
            try {
                const epssResp = await fetch(`https://api.first.org/data/v1/epss?cve=${cveId}`);
                if (epssResp.ok) {
                    const epssData = await epssResp.json();
                    if (epssData.data && epssData.data.length > 0) {
                        vuln.epss = parseFloat(epssData.data[0].epss);
                        vuln.percentile = parseFloat(epssData.data[0].percentile);
                    }
                }
            } catch (e) {
                console.warn(`EPSS fetch failed for ${cveId}`);
            }

            // Check KEV status
            try {
                const kevResp = await fetch(`api/kev_proxy.php?cve=${cveId}`);
                if (kevResp.ok) {
                    const kevData = await kevResp.json();
                    vuln.inKEV = kevData.exists === true;
                }
            } catch (e) {
                console.warn(`KEV check failed for ${cveId}`);
                vuln.inKEV = false;
            }
        }));

        // Rate limiting - wait between batches
        if (i + batchSize < vulnerabilities.length) {
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
    }
}

// Calculate statistics
function calculateStats(vulnerabilities) {
    let totalCVSS = 0;
    let cvssCount = 0;
    let totalEPSS = 0;
    let epssCount = 0;
    let kevCount = 0;

    const severityCounts = {
        CRITICAL: 0,
        HIGH: 0,
        MEDIUM: 0,
        LOW: 0,
        NONE: 0
    };

    const cweMap = {};

    vulnerabilities.forEach(vuln => {
        const cve = vuln.cve;

        // CVSS Score
        const metrics = cve.metrics;
        let cvssScore = null;

        if (metrics?.cvssMetricV31?.[0]?.cvssData?.baseScore) {
            cvssScore = metrics.cvssMetricV31[0].cvssData.baseScore;
        } else if (metrics?.cvssMetricV30?.[0]?.cvssData?.baseScore) {
            cvssScore = metrics.cvssMetricV30[0].cvssData.baseScore;
        } else if (metrics?.cvssMetricV2?.[0]?.cvssData?.baseScore) {
            cvssScore = metrics.cvssMetricV2[0].cvssData.baseScore;
        }

        if (cvssScore !== null) {
            totalCVSS += cvssScore;
            cvssCount++;
            vuln.cvssScore = cvssScore;

            // Determine severity
            if (cvssScore >= 9.0) {
                severityCounts.CRITICAL++;
                vuln.severity = 'CRITICAL';
            } else if (cvssScore >= 7.0) {
                severityCounts.HIGH++;
                vuln.severity = 'HIGH';
            } else if (cvssScore >= 4.0) {
                severityCounts.MEDIUM++;
                vuln.severity = 'MEDIUM';
            } else if (cvssScore > 0) {
                severityCounts.LOW++;
                vuln.severity = 'LOW';
            } else {
                severityCounts.NONE++;
                vuln.severity = 'NONE';
            }
        }

        // EPSS Score
        if (vuln.epss !== undefined) {
            totalEPSS += vuln.epss;
            epssCount++;
        }

        // KEV Count
        if (vuln.inKEV) {
            kevCount++;
        }

        // CWE tracking
        const weaknesses = cve.weaknesses;
        if (weaknesses && weaknesses.length > 0) {
            weaknesses.forEach(weakness => {
                weakness.description.forEach(desc => {
                    if (desc.value && desc.value.startsWith('CWE-')) {
                        cweMap[desc.value] = (cweMap[desc.value] || 0) + 1;
                    }
                });
            });
        }
    });

    // Update UI
    document.getElementById('avgCVSS').textContent = cvssCount > 0 ? (totalCVSS / cvssCount).toFixed(2) : 'N/A';
    document.getElementById('avgEPSS').textContent = epssCount > 0 ? (totalEPSS / epssCount).toFixed(4) : 'N/A';
    document.getElementById('kevCount').textContent = kevCount;
    document.getElementById('totalCVE').textContent = vulnerabilities.length;

    // Average severity
    const avgSeverityScore = cvssCount > 0 ? (totalCVSS / cvssCount) : 0;
    let avgSeverityLabel = 'N/A';
    if (avgSeverityScore >= 9.0) avgSeverityLabel = 'CRITICAL';
    else if (avgSeverityScore >= 7.0) avgSeverityLabel = 'HIGH';
    else if (avgSeverityScore >= 4.0) avgSeverityLabel = 'MEDIUM';
    else if (avgSeverityScore > 0) avgSeverityLabel = 'LOW';

    document.getElementById('avgSeverity').textContent = avgSeverityLabel;

    // Most frequent CWE
    let topCWE = 'N/A';
    let maxCount = 0;
    for (const [cwe, count] of Object.entries(cweMap)) {
        if (count > maxCount) {
            maxCount = count;
            topCWE = cwe;
        }
    }
    document.getElementById('topCWE').textContent = `${topCWE} (${maxCount})`;

    // Store severity counts for chart
    currentData.severityCounts = severityCounts;
}

// Display charts
function displayCharts(vulnerabilities) {
    displayCVSSTrendChart(vulnerabilities);
    displaySeverityChart();
}

// Display CVSS trend over time
function displayCVSSTrendChart(vulnerabilities) {
    // Group by date
    const dateMap = {};

    vulnerabilities.forEach(vuln => {
        if (vuln.cvssScore === undefined) return;

        const pubDate = vuln.cve.published;
        const date = pubDate.split('T')[0]; // YYYY-MM-DD

        if (!dateMap[date]) {
            dateMap[date] = { scores: [], count: 0 };
        }

        dateMap[date].scores.push(vuln.cvssScore);
        dateMap[date].count++;
    });

    // Calculate average per date
    const dates = Object.keys(dateMap).sort();
    const avgScores = dates.map(date => {
        const scores = dateMap[date].scores;
        return scores.reduce((a, b) => a + b, 0) / scores.length;
    });

    const ctx = document.getElementById('cvssChart').getContext('2d');

    if (cvssChart) {
        cvssChart.destroy();
    }

    cvssChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'CVSS Moyen',
                data: avgScores,
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    title: {
                        display: true,
                        text: 'Score CVSS'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    ticks: {
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });
}

// Display severity distribution chart
function displaySeverityChart() {
    const ctx = document.getElementById('severityChart').getContext('2d');

    if (severityChart) {
        severityChart.destroy();
    }

    const counts = currentData.severityCounts;

    severityChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'NONE'],
            datasets: [{
                data: [
                    counts.CRITICAL,
                    counts.HIGH,
                    counts.MEDIUM,
                    counts.LOW,
                    counts.NONE
                ],
                backgroundColor: [
                    'rgb(220, 53, 69)',
                    'rgb(255, 193, 7)',
                    'rgb(23, 162, 184)',
                    'rgb(108, 117, 125)',
                    'rgb(200, 200, 200)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Display top vulnerabilities
function displayTopVulnerabilities(vulnerabilities) {
    // Top 10 CVSS
    const topCVSS = [...vulnerabilities]
        .filter(v => v.cvssScore !== undefined)
        .sort((a, b) => b.cvssScore - a.cvssScore)
        .slice(0, 10);

    displayVulnList(topCVSS, 'topCVSS', 'cvss');

    // Top 10 EPSS
    const topEPSS = [...vulnerabilities]
        .filter(v => v.epss !== undefined)
        .sort((a, b) => b.epss - a.epss)
        .slice(0, 10);

    displayVulnList(topEPSS, 'topEPSS', 'epss');
}

// Display vulnerability list
function displayVulnList(vulnerabilities, containerId, scoreType) {
    const container = document.getElementById(containerId);

    if (vulnerabilities.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">Aucune donnée disponible</p>';
        return;
    }

    let html = '';

    vulnerabilities.forEach((vuln, index) => {
        const cveId = vuln.cve.id;
        const description = vuln.cve.descriptions?.[0]?.value || 'No description available';
        const shortDesc = description.length > 150 ? description.substring(0, 150) + '...' : description;

        let score, scoreLabel, badgeClass;

        if (scoreType === 'cvss') {
            score = vuln.cvssScore.toFixed(1);
            scoreLabel = 'CVSS';
            badgeClass = getSeverityClass(vuln.severity);
        } else {
            score = (vuln.epss * 100).toFixed(2) + '%';
            scoreLabel = 'EPSS';
            badgeClass = 'bg-warning text-dark';
        }

        const kevBadge = vuln.inKEV ? '<span class="badge bg-danger ms-2">KEV</span>' : '';

        html += `
            <div class="card vuln-item ${vuln.severity ? vuln.severity.toLowerCase() : ''}" onclick="showCVEDetails('${cveId}')">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <strong>${cveId}</strong>
                            <span class="badge ${badgeClass}">${scoreLabel}: ${score}</span>
                            ${kevBadge}
                        </h6>
                        <p class="mb-0 small text-muted">${shortDesc}</p>
                    </div>
                    <div class="text-end ms-3">
                        <span class="badge bg-secondary">#${index + 1}</span>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Get severity CSS class
function getSeverityClass(severity) {
    const classes = {
        'CRITICAL': 'bg-danger',
        'HIGH': 'bg-warning text-dark',
        'MEDIUM': 'bg-info',
        'LOW': 'bg-secondary',
        'NONE': 'bg-light text-dark'
    };
    return classes[severity] || 'bg-secondary';
}

// Show loading animation
function showLoadingAnimation() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loadingOverlay';
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-primary fw-bold">Analyzing CVEs...</p>
            <p class="text-muted small">Fetching and enriching vulnerability data</p>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
}

// Hide loading animation
function hideLoadingAnimation() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// Show CVE details in modal
async function showCVEDetails(cveId) {
    const modal = new bootstrap.Modal(document.getElementById('cveModal'));
    const modalBody = document.getElementById('cveModalBody');
    const modalLabel = document.getElementById('cveModalLabel');
    const nvdLink = document.getElementById('cveModalNvdLink');

    modalLabel.textContent = cveId;
    nvdLink.href = `https://nvd.nist.gov/vuln/detail/${cveId}`;
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

    modal.show();

    try {
        const url = `https://services.nvd.nist.gov/rest/json/cves/2.0?cveId=${encodeURIComponent(cveId)}`;
        const response = await fetch(url);

        if (!response.ok) throw new Error('Failed to fetch CVE details');

        const data = await response.json();
        const vuln = data.vulnerabilities?.[0];

        if (!vuln) throw new Error('CVE not found');

        const cve = vuln.cve;
        const description = cve.descriptions?.[0]?.value || 'No description available';

        // Get CVSS score
        const metrics = cve.metrics;
        let cvssInfo = 'N/A';

        if (metrics?.cvssMetricV31?.[0]) {
            const cvss = metrics.cvssMetricV31[0];
            cvssInfo = `
                <strong>CVSS v3.1:</strong> ${cvss.cvssData.baseScore} (${cvss.cvssData.baseSeverity})<br>
                <strong>Vector:</strong> ${cvss.cvssData.vectorString}
            `;
        } else if (metrics?.cvssMetricV30?.[0]) {
            const cvss = metrics.cvssMetricV30[0];
            cvssInfo = `
                <strong>CVSS v3.0:</strong> ${cvss.cvssData.baseScore} (${cvss.cvssData.baseSeverity})<br>
                <strong>Vector:</strong> ${cvss.cvssData.vectorString}
            `;
        } else if (metrics?.cvssMetricV2?.[0]) {
            const cvss = metrics.cvssMetricV2[0];
            cvssInfo = `
                <strong>CVSS v2:</strong> ${cvss.cvssData.baseScore} (${cvss.baseSeverity})<br>
                <strong>Vector:</strong> ${cvss.cvssData.vectorString}
            `;
        }

        // Get CWE
        let cweInfo = 'N/A';
        if (cve.weaknesses && cve.weaknesses.length > 0) {
            cweInfo = cve.weaknesses.map(w =>
                w.description.map(d => d.value).join(', ')
            ).join(', ');
        }

        // Get references
        let refsHtml = '';
        if (cve.references && cve.references.length > 0) {
            refsHtml = '<ul class="list-unstyled">';
            cve.references.slice(0, 5).forEach(ref => {
                refsHtml += `<li><a href="${ref.url}" target="_blank" class="text-break">${ref.url}</a></li>`;
            });
            if (cve.references.length > 5) {
                refsHtml += `<li class="text-muted">... and ${cve.references.length - 5} more</li>`;
            }
            refsHtml += '</ul>';
        }

        modalBody.innerHTML = `
            <div class="mb-3">
                <h6>Description</h6>
                <p>${description}</p>
            </div>
            <div class="mb-3">
                <h6>CVSS Score</h6>
                <p>${cvssInfo}</p>
            </div>
            <div class="mb-3">
                <h6>CWE</h6>
                <p>${cweInfo}</p>
            </div>
            <div class="mb-3">
                <h6>Published</h6>
                <p>${new Date(cve.published).toLocaleString()}</p>
            </div>
            ${refsHtml ? `<div class="mb-3"><h6>References</h6>${refsHtml}</div>` : ''}
        `;

    } catch (error) {
        console.error('Error fetching CVE details:', error);
        modalBody.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails de la CVE.</div>';
    }
}

// Reset stats
function resetStats() {
    document.getElementById('avgCVSS').textContent = '-';
    document.getElementById('avgEPSS').textContent = '-';
    document.getElementById('kevCount').textContent = '-';
    document.getElementById('totalCVE').textContent = '-';
    document.getElementById('avgSeverity').textContent = '-';
    document.getElementById('topCWE').textContent = '-';

    document.getElementById('topCVSS').innerHTML = '<div class="text-center"><div class="loading-spinner"></div></div>';
    document.getElementById('topEPSS').innerHTML = '<div class="text-center"><div class="loading-spinner"></div></div>';
}

// Check URL parameters on load
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const keyword = params.get('keyword');
    const timeRange = params.get('time_range');

    if (keyword) {
        document.getElementById('keyword').value = keyword;
        if (timeRange) {
            document.getElementById('timeRange').value = timeRange;
        }
        performAnalysis(keyword, parseInt(timeRange || '90'));
    }
});

