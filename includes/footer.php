        </main>

        <!-- 5. FOOTER -->
        <footer class="area-footer">
            <p>© 2026 HovaHM System - Bản quyền thuộc về tụi tui</p>
        </footer>
    </div>

    <script>
        document.querySelectorAll('.menu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                this.parentElement.classList.toggle('open');
            });
        });
    </script>
</body>
</html>
