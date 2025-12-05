function hideLoadingAnimation() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
    document.body.style.overflow = "auto";
}

function showLoadingAnimation(title, subTitle) {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loadingOverlay';
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-primary fw-bold">${title}</p>
            <p class="text-muted small">${subTitle}</p>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
    document.body.style.overflow = "hidden";
}