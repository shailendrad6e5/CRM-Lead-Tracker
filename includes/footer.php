    <?php if (isLoggedIn()): ?>
            </div> <!-- Close container-fluid -->
        </div> <!-- Close content wrapper -->
    </div> <!-- Close wrapper flex -->
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-danger text-white pb-2">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-0 fs-5">Are you sure you want to delete this record?</p>
                    <p class="text-muted small mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="">
                        <input type="hidden" name="id" id="deleteId">
                        <?= isLoggedIn() ? csrfField() : '' ?>
                        <button type="submit" class="btn btn-danger px-4">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js (loaded only when needed) -->
    <?php if (isset($loadCharts) && $loadCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
