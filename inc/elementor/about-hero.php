<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FR_About_Hero_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'fr-about-hero';
    }

    public function get_title() {
        return esc_html__( 'Flex Rock About Hero', 'fr-astra-child' );
    }

    public function get_icon() {
        return 'eicon-image-box';
    }

    public function get_categories() {
        return array( 'general' );
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__( 'Content', 'fr-astra-child' ),
            )
        );

        $this->add_control(
            'heading',
            array(
                'label'       => esc_html__( 'Heading', 'fr-astra-child' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__( 'Why Us?', 'fr-astra-child' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'description',
            array(
                'label'   => esc_html__( 'Description', 'fr-astra-child' ),
                'type'    => \Elementor\Controls_Manager::WYSIWYG,
                'default' => '
                    <p>We\'re passionate riders and skilled engineers with deep respect for the off-road adventure.</p>
                    <p>All our products are designed and manufactured in-house using top tier materials.</p>
                ',
            )
        );

        $this->add_control(
            'image',
            array(
                'label' => esc_html__( 'Image', 'fr-astra-child' ),
                'type'  => \Elementor\Controls_Manager::MEDIA,
            )
        );

        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();
        ?>

        <section class="fr-about-hero">

            <div class="fr-about-hero__content">

                <?php if ( ! empty( $settings['heading'] ) ) : ?>
                    <h1>
                        <?php echo esc_html( $settings['heading'] ); ?>
                    </h1>
                <?php endif; ?>

                <span class="fr-about-hero__rule"></span>

                <?php if ( ! empty( $settings['description'] ) ) : ?>
                    <div class="fr-about-hero__text">
                        <?php echo wp_kses_post( $settings['description'] ); ?>
                    </div>
                <?php endif; ?>

            </div>

            <?php if ( ! empty( $settings['image']['id'] ) ) : ?>

                <div class="fr-about-hero__image">

                    <?php
                    echo \Elementor\Group_Control_Image_Size::get_attachment_image_html(
                        $settings,
                        'image',
                        'image'
                    );
                    ?>

                </div>

            <?php endif; ?>

        </section>

        <?php
    }
}