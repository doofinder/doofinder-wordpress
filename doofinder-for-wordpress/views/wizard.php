<?php

namespace Doofinder\WP;

/** @var Setup_Wizard $this */

?>

<html>
<head>
    <title><?php printf( __( '%s - Doofinder Setup Wizard', 'doofinder_for_wp' ), get_bloginfo( 'name' ) ); ?></title>

    <link rel="stylesheet" href="<?php echo Doofinder_For_WordPress::plugin_url(); ?>assets/css/wizard.css">
</head>

<body>

    <div class="box">
        <h1><?php _e( 'Doofinder Setup Wizard', 'doofinder_for_wp' ); ?></h1>

		<?php $this->render_wizard_step(); ?>
    </div>

</body>
</html>
