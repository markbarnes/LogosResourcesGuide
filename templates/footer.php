            <div id="footer">
              <div class="container">
                <table>
                    <tr>
                        <td>This site is being developed by Mark Barnes, and is not endorsed by Faithlife. It is in beta which means some things won&rsquo;t work as expected. Please report problems to the Logos forums.</td>
                    </tr>
                </table>
              </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <?php
        if (isset ($include_in_footer)) {
            foreach ($include_in_footer as $file => $params) {
                require ("templates/footer-includes/{$file}.php");
            }
        }
    ?>
  </body>
</html>