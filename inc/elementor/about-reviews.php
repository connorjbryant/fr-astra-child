<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FR_About_Reviews_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'fr-about-reviews';
    }

    public function get_title() {
        return esc_html__(
            'Flex Rock About Reviews',
            'fr-astra-child'
        );
    }

    public function get_icon() {
        return 'eicon-testimonial';
    }

    public function get_categories() {
        return array( 'general' );
    }

    public function get_style_depends() {
        return array( 'fr-about-style' );
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__(
                    'Reviews',
                    'fr-astra-child'
                ),
            )
        );

        $this->add_control(
            'heading',
            array(
                'label'       => esc_html__(
                    'Heading',
                    'fr-astra-child'
                ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__(
                    'Rider Reviews',
                    'fr-astra-child'
                ),
                'label_block' => true,
            )
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'review',
            array(
                'label' => esc_html__(
                    'Review',
                    'fr-astra-child'
                ),
                'type'  => \Elementor\Controls_Manager::TEXTAREA,
                'rows'  => 5,
            )
        );

        $repeater->add_control(
            'name',
            array(
                'label' => esc_html__(
                    'Name',
                    'fr-astra-child'
                ),
                'type'  => \Elementor\Controls_Manager::TEXT,
            )
        );

        $this->add_control(
            'reviews',
            array(
                'label'       => esc_html__(
                    'Reviews',
                    'fr-astra-child'
                ),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'title_field' => '{{{ name }}}',
                'default'     => array(
                    array(
                        'review' => 'Awesome upgrade for my RZR Pro XP4! Much better grease point than stock and best of all no more squeak.',
                        'name'   => 'Kyle Cole',
                    ),
                    array(
                        'review' => 'These things are awesome. Way better than factory and I am very impressed.',
                        'name'   => 'Jeremy Ecott',
                    ),
                    array(
                        'review' => 'Customer service was very professional and fast. Pricing was great.',
                        'name'   => 'Dirk Hinton',
                    ),
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();

        if (
            empty( $settings['heading'] )
            && empty( $settings['reviews'] )
        ) {
            return;
        }
        ?>

        <section class="fr-about-reviews">

            <div class="fr-about-reviews__inner">

                <?php if ( ! empty( $settings['heading'] ) ) : ?>

                    <header class="fr-about-reviews__header">

                        <h2>
                            <?php echo esc_html(
                                $settings['heading']
                            ); ?>
                        </h2>

                        <span
                            class="fr-about-reviews__header-rule"
                            aria-hidden="true"
                        ></span>

                    </header>

                <?php endif; ?>

                <?php if ( ! empty( $settings['reviews'] ) ) : ?>

                    <div class="fr-about-reviews__grid">

                        <?php foreach ( $settings['reviews'] as $review ) : ?>

                            <article class="fr-about-review">

                                <span
                                    class="fr-about-review__mark"
                                    aria-hidden="true"
                                >
                                    &ldquo;
                                </span>

                                <?php if ( ! empty( $review['review'] ) ) : ?>

                                    <p class="fr-about-review__text">
                                        <?php echo esc_html(
                                            $review['review']
                                        ); ?>
                                    </p>

                                <?php endif; ?>

                                <?php if ( ! empty( $review['name'] ) ) : ?>

                                    <p class="fr-about-review__name">
                                        <?php echo esc_html(
                                            $review['name']
                                        ); ?>
                                    </p>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>

        <?php
    }
}