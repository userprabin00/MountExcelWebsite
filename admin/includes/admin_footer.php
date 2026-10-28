      </main>

      <!-- Admin Footer -->
      <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> Mount Excel Boarding School &bull; Staff Administration Portal</p>
      </footer>
    </div>
  </div>

  <script src="../js/admin.js?v=<?= e(file_exists(__DIR__ . '/../../js/admin.js') ? filemtime(__DIR__ . '/../../js/admin.js') : time()) ?>"></script>
</body>
</html>
