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

    public function get_style_depends() {
        return array(
            'fr-about-style',
            'fr-about-oxanium',
        );
    }

    protected function register_controls() {

        /*
         * =========================================
         * Content
         * =========================================
         */

        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__( 'Content', 'fr-astra-child' ),
            )
        );

        /*
         * Heading
         */
        $this->add_control(
            'heading',
            array(
                'label'       => esc_html__( 'Heading', 'fr-astra-child' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__( 'Why Us?', 'fr-astra-child' ),
                'label_block' => true,
            )
        );

        /*
         * Description
         */
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

        /*
         * Image
         */
        $this->add_control(
            'image',
            array(
                'label' => esc_html__( 'Image', 'fr-astra-child' ),
                'type'  => \Elementor\Controls_Manager::MEDIA,
            )
        );

        /*
         * Image Position
         */
        $this->add_control(
            'image_position',
            array(
                'label'   => esc_html__( 'Image Position', 'fr-astra-child' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'right',
                'options' => array(
                    'right' => esc_html__( 'Right', 'fr-astra-child' ),
                    'left'  => esc_html__( 'Left', 'fr-astra-child' ),
                ),
            )
        );

        /*
         * End Content Section
         */
        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();

        /*
         * Sanitize image position so only our two
         * expected modifier classes can be output.
         */
        $image_position =
            isset( $settings['image_position'] )
            && in_array(
                $settings['image_position'],
                array( 'left', 'right' ),
                true
            )
                ? $settings['image_position']
                : 'right';

        $section_classes = array(
            'fr-about-hero',
            'fr-about-hero--image-' . $image_position,
        );
        ?>

        <section
            class="<?php echo esc_attr( implode( ' ', $section_classes ) ); ?>"
        >

            <div class="fr-about-hero__content">

                <?php if ( ! empty( $settings['heading'] ) ) : ?>

                    <h1>
                        <?php echo esc_html( $settings['heading'] ); ?>
                    </h1>

                <?php endif; ?>

                <span
                    class="fr-about-hero__rule"
                    aria-hidden="true"
                ></span>

                <?php if ( ! empty( $settings['description'] ) ) : ?>

                    <div class="fr-about-hero__text">

                        <?php
                        echo wp_kses_post(
                            $settings['description']
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>

            <?php if ( ! empty( $settings['image']['id'] ) ) : ?>

                <div class="fr-about-hero__image">

                    <?php
                    echo wp_get_attachment_image(
                        absint( $settings['image']['id'] ),
                        'full',
                        false,
                        array(
                            'class' => 'fr-about-hero__img',
                        )
                    );
                    ?>

                </div>

            <?php endif; ?>

        </section>

        <?php
    }
}