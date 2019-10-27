<?php

namespace DataSync;

use DataSync\Controllers\Options;
use DataSync\Controllers\Posts;
use DataSync\Controllers\PostTypes;
use DataSync\Models\ConnectedSite;
use DataSync\Models\SyncedPost;

function display_syndicated_posts_table() {
    ?>
    <div id="status_dashboard_button_wrap">
        <button id="refresh_syndicated_posts"
                class="button button-secondary"><?php _e( 'Refresh', 'data_sync' ); ?></button>
        <button id="bulk_data_push" class="button button-primary"><?php _e( 'Sync All', 'data_sync' ); ?></button>
    </div>
    <table id="wp_data_sync_status">
        <thead>
        <tr>
            <th><?php _e( 'ID', 'data_sync' ); ?></th>
            <th><?php _e( 'TITLE', 'data_sync' ); ?></th>
            <th><?php _e( 'TYPE', 'data_sync' ); ?></th>
            <th><?php _e( 'STATUS', 'data_sync' ); ?></th>
            <th><?php _e( 'DETAILS', 'data_sync' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php


        $source_options  = Options::source();
        $connected_sites = (array) ConnectedSite::get_all();
        if ( empty( $source_options->push_enabled_post_types ) ) {
            echo '<tr><td colspan="5" class="data_error">Required plugins not installed. Please turn on debugging and view error log for more details.</td></tr>';
        }
        $post_types                  = array_keys( $source_options->push_enabled_post_types );
        $posts                       = Posts::get_wp_posts( $post_types, true );

        if ( count( $posts ) ) {

            $receiver_posts              = Posts::get_all_receiver_posts( $connected_sites );
            $enabled_post_type_site_data = PostTypes::get_all_enabled_post_types_from_receivers( $connected_sites );

            foreach ( $posts as $post ) {
                $syndication_info = Posts::get_syndication_info_of_post( $post, $connected_sites, $receiver_posts );
                $post_type_obj    = get_post_type_object( $post->post_type ); ?>
                <tr class="top_level_post_detail" data-id="<?php echo $post->ID ?>"
                    id="synced_post-<?php echo $post->ID ?>">
                    <td><?php echo esc_html( $post->ID ); ?></td>
                    <td>
                        <a class="<?php echo $syndication_info->trash_class ?>"
                           href="/wp-admin/post.php?post=<?php echo $post->ID; ?>&action=edit"
                           target="_blank"><?php echo esc_html( $post->post_title ); ?></a>
                    </td>

                    <td><?php echo esc_html( $post_type_obj->label ); ?></td>
                    <td class="wp_data_synced_post_status_icons"><?php echo $syndication_info->icon; ?></td>
                    <td class="expand_post_details noselect" data-id="<?php echo $post->ID ?>">+</td>
                </tr>
                <tr class="post_details" id="post-<?php echo $post->ID ?>">
                    <td class="post_detail_wrap" colspan="5">
                        <div class="source_details">
                            <h4>Source Info</h4>
                            <?php echo $syndication_info->source_message ?>
                        </div>
                        <div class="detail_wrap"><?php echo display_post_syndication_details_per_site( $syndication_info, $connected_sites, $post, $enabled_post_type_site_data, $receiver_posts ); ?></div>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="5" class="data_error">No posts.</td></tr>';
        } ?>
        </tbody>
    </table>
    <?php
}


function display_post_syndication_details_per_site( $syndication_info, $connected_sites, $post, $enabled_post_type_site_data, $receiver_posts ) {
    ?>
    <div class="connected_site_info">
    <h4>Connected Site Info</h4>
    <?php

    foreach ( $connected_sites as $index => $site ) {
        $result = SyncedPost::get_where( array(
                'source_post_id'   => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ),
                'receiver_site_id' => (int) filter_var( $site->id, FILTER_SANITIZE_NUMBER_INT ),
            ) );

        $connected_site_sync_date   = strtotime($site->sync_start);
        $source_modified_date = strtotime($post->post_modified_gmt);
        $connected_site_synced_post = ( ! empty( $result[0] ) ) ? $result[0] : false; ?>

        <strong>Site ID: <?php echo $site->id ?> &middot; <?php echo $site->url ?></strong>
        <div class="details">
            <?php

            $post_meta      = get_post_meta( $post->ID );
            $excluded_sites = unserialize( $post_meta['_excluded_sites'][0] );

            if ( in_array( (int) $site->id, $excluded_sites ) ) {
                ?><span class="none_enabled"><strong>Receiver excluded in post.</strong></span><?php

                if ( (int) $site->id === (int) $post_meta['_canonical_site'][0] ) {
                    ?><span class="none_enabled"><strong>This post's canonical URL is pointing to a receiver that is excluded.</strong></span><?php
                }
            }

            foreach ( $enabled_post_type_site_data as $enabled_post_type_site_datum ) {
                if ( (int) $site->id === $enabled_post_type_site_datum->site_id ) {
                    $enabled_post_types = $enabled_post_type_site_datum->enabled_post_types;
                    break;
                }
            }

            if ( empty( $enabled_post_types ) ) {
                ?><span class="none_enabled"><strong>No enabled post types on this site.</strong></span><?php

                if ( (int) $site->id === (int) $post_meta['_canonical_site'][0] ) {
                    ?><span class="none_enabled"><strong>This post's canonical URL is pointing to this receiver that doesn't have any post types enabled. No syndication will happen and SEO errors will occur. Please enable post types on this receiver or change the canonical site of this post.</strong></span><?php
                }
            }

            if ( ! empty( $connected_site_synced_post ) ) {
                $local_timestamp = date( 'g:i:s a n/d/Y', get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $connected_site_synced_post->date_modified ) ), 'U' ) );

                echo '<span>Last syndication: ' . $local_timestamp . '</span>';

                // NEED TO GET PER-SITE DATA. CAN'T RELY ON DATA FROM $syndication_info BECAUSE THAT IS POST-SPECIFIC, NOT SITE AND POST SPECIFIC.
                $synced_post_modified_time = strtotime( $connected_site_synced_post->date_modified );
                $source_post_modified_time = strtotime( $post->post_modified_gmt );
                $receiver_post             = Posts::find_receiver_post( $receiver_posts, $connected_site_synced_post->receiver_site_id, $connected_site_synced_post->receiver_post_id );
                $receiver_modified_time    = strtotime( $receiver_post->post_modified_gmt );

                if ( $receiver_modified_time > $synced_post_modified_time ) {
                    $sync_status = 'diverged';
                } elseif ( $source_post_modified_time > $synced_post_modified_time ) {
                    $sync_status = 'diverged';
                } elseif ( $synced_post_modified_time >= $receiver_modified_time ) {
                    $sync_status = 'synced';
                }

                if ( 'diverged' === $sync_status ) {
                    $site_status_icon = '<span>Status: <i class="dashicons dashicons-editor-unlink"></i></span>';

                    if ( ( $syndication_info->source_version_edited ) && ( ( $syndication_info->receiver_version_edited[0] ) && ( (int) $connected_site_synced_post->receiver_site_id === (int) $syndication_info->receiver_version_edited[1] ) ) ) {
                        echo '<span class="warning">Source AND receiver updated since last sync.</span>';
                    } elseif ( ( $syndication_info->receiver_version_edited[0] ) && ( (int) $connected_site_synced_post->receiver_site_id === (int) $syndication_info->receiver_version_edited[1] ) ) {
                        echo '<span class="warning">Receiver post was updated after the last sync.</span>';
                    }

                    echo '<br>';
                    echo '<button class="button danger_button overwrite_single_receiver" data-receiver-site-id="' . $connected_site_synced_post->receiver_site_id . '" data-source-post-id="' . $syndication_info->synced_post->source_post_id . '">Overwrite this receiver</button>';
                } else {
                    // SYNCED.
                    $site_status_icon = '<span>Status: <i class="dashicons dashicons-yes" title="Synced on this connected site."></i></span>';
                }
            } elseif ( in_array( (int) $site->id, $excluded_sites ) ) {
                // NOT SYNCED ON PURPOSE BECAUSE OF EXCLUDED SITE.
                echo '<span>Last syndication: Never.';
                $site_status_icon = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
            } elseif ($source_modified_date < $connected_site_sync_date) {
                // NOT SYNCED ON PURPOSE BECAUSE OF SYNC START DATE.
                echo '<span>Last syndication: Never.';
                echo '<span class="warning">This post hasn\'t been modified after this site\'s sync start date.</span>';
                $site_status_icon = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
            } else {
                // NOT SYNCED.
                echo '<span>Last syndication: Never.';
                $site_status_icon = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
                $site_status_icon .= '<button class="button danger_button overwrite_single_receiver" data-receiver-site-id="' . $site->id . '" data-source-post-id="' . $post->ID . '">Overwrite this receiver</button>';
            }


            echo $site_status_icon; ?>
        </div>
        </div>
        <?php
    }
}
