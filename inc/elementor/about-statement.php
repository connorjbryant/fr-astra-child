<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FR_About_Statement_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'fr-about-statement';
    }

    public function get_title() {
        return esc_html__(
            'Flex Rock About Statement',
            'fr-astra-child'
        );
    }

    public function get_icon() {
        return 'eicon-blockquote';
    }

    public function get_categories() {
        return array( 'general' );
    }

    public function get_style_depends() {
        return array(
            'fr-about-style',
        );
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__(
                    'Statement',
                    'fr-astra-child'
                ),
            )
        );

        $this->add_control(
            'statement',
            array(
                'label'       => esc_html__(
                    'Statement',
                    'fr-astra-child'
                ),
                'type'        => \Elementor\Controls_Manager::TEXTAREA,
                'default'     => esc_html__(
                    'The highest performing, best looking products available — since 2018.',
                    'fr-astra-child'
                ),
                'rows'        => 4,
                'label_block' => true,
            )
        );

        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();

        if ( empty( $settings['statement'] ) ) {
            return;
        }
        ?>

        <section class="fr-about-statement">

            <blockquote class="fr-about-statement__quote">

                <span
                    class="fr-about-statement__mark"
                    aria-hidden="true"
                >
                    &ldquo;
                </span>

                <p class="fr-about-statement__text">
                    <?php
                    echo esc_html(
                        $settings['statement']
                    );
                    ?>
                </p>

            </blockquote>

        </section>

        <?php
    }
}