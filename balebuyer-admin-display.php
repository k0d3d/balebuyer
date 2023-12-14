<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
  <form method="post" action="options.php">
    <?php
    // Output nonce, action, and option_page fields for a settings page
    settings_fields('hide_price_options_group');
    // Output settings sections and their fields
    do_settings_sections('hide_price_options_page');
    submit_button();
    ?>
  </form>
</div>