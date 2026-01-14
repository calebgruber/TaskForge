        <?php if (isLoggedIn()): ?>
        </div>
    </div>
    <?php else: ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <?php if (isset($pageScript)): ?>
    <script>
        <?php echo $pageScript; ?>
    </script>
    <?php endif; ?>
</body>
</html>
