        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-300">
                        © <?php echo date('Y'); ?> Teaching Management System. All rights reserved.
                    </p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-300 hover:text-white text-sm">
                        <i class="fas fa-question-circle mr-1"></i>Help
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white text-sm">
                        <i class="fas fa-cog mr-1"></i>Settings
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Common JavaScript functions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100, .bg-blue-100, .bg-yellow-100');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.style.transition = 'opacity 0.5s';
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.remove();
                            }
                        }, 500);
                    }
                }, 5000);
            });
        });

        // Modal functionality
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('fixed') && event.target.classList.contains('bg-gray-600')) {
                event.target.classList.add('hidden');
            }
        });

        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        // Table sorting functionality
        function sortTable(tableId, columnIndex) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                return aValue.localeCompare(bValue);
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        // Search functionality
        function filterTable(tableId, searchTerm) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            const searchLower = searchTerm.toLowerCase();

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchLower)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Pagination functionality
        function showPage(pageNumber, totalPages, tableId) {
            const rowsPerPage = 10;
            const startIndex = (pageNumber - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;

            const table = document.getElementById(tableId);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach((row, index) => {
                if (index >= startIndex && index < endIndex) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Update pagination controls
            updatePaginationControls(pageNumber, totalPages);
        }

        function updatePaginationControls(currentPage, totalPages) {
            const paginationContainer = document.querySelector('.pagination');
            if (!paginationContainer) return;

            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `<button onclick="showPage(${currentPage - 1}, ${totalPages}, 'dataTable')" 
                class="px-3 py-1 border rounded ${currentPage === 1 ? 'bg-gray-300 cursor-not-allowed' : 'bg-white hover:bg-gray-50'}" 
                ${currentPage === 1 ? 'disabled' : ''}>Previous</button>`;

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += `<button onclick="showPage(${i}, ${totalPages}, 'dataTable')" 
                    class="px-3 py-1 border rounded mx-1 ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-50'}">${i}</button>`;
            }

            // Next button
            paginationHTML += `<button onclick="showPage(${currentPage + 1}, ${totalPages}, 'dataTable')" 
                class="px-3 py-1 border rounded ${currentPage === totalPages ? 'bg-gray-300 cursor-not-allowed' : 'bg-white hover:bg-gray-50'}" 
                ${currentPage === totalPages ? 'disabled' : ''}>Next</button>`;

            paginationContainer.innerHTML = paginationHTML;
        }
    </script>
        </main>
    </body>
</html> 