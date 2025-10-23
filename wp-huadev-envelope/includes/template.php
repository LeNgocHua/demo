<?php
$bg = esc_attr($atts['bg_color']);
$env = esc_attr($atts['envelope_color']);
$p1 = esc_attr($atts['pocket_color1']);
$p2 = esc_attr($atts['pocket_color2']);
$emoji = wp_kses_post($atts['seal_emoji']);
$img = esc_url($atts['image_url']);
$float = $atts['float'] === '1';
?>
<div class="huadev" style="background: <?php echo $bg; ?>;">
    <div class="envelope <?php echo $float ? 'floating' : ''; ?>" onclick="this.classList.toggle('open')" style="background-color: <?php echo $env; ?>;">
        <div class="flap" style="border-color: <?php echo $env; ?> transparent transparent;"></div>
        <div class="pocket" style="border-color: transparent <?php echo $p1; ?> <?php echo $p2; ?>;"></div>
        <div class="seal" data-emoji="<?php echo esc_attr($emoji); ?>"></div>
        <div class="letter">
            <img src="<?php echo $img; ?>" alt="Envelope image" width="100%" height="100%" />
        </div>
    </div>
</div>
