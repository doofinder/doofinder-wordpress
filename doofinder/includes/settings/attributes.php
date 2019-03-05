<?php

$attributes = array(
	'post_title' => array(
		'label'  => __( 'Attribute: Post Title', 'doofinder_for_wp' ),
		'type'   => 'field',
		'source' => 'post_title',
	),

	'post_content' => array(
		'label'  => __( 'Attribute: Post Content', 'doofinder_for_wp' ),
		'type'   => 'field',
		'source' => 'post_content',
	),

	'excerpt' => array(
		'label'  => __( 'Attribute: Post Excerpt', 'doofinder_for_wp' ),
		'type'   => 'generated',
		'source' => 'post_excerpt',
	),

	'permalink' => array(
		'label'  => __( 'Attribute: Post Permalink', 'doofinder_for_wp' ),
		'type'   => 'generated',
		'source' => 'permalink',
	),

	'thumbnail' => array(
		'label'  => __( 'Attribute: Post Thumbnail', 'doofinder_for_wp' ),
		'type'   => 'generated',
		'source' => 'thumbnail',
	)
);

return $attributes;