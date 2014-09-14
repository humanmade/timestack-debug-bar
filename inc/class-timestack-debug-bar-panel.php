<?php

class Timestack_Debug_Bar_Panel extends Debug_Bar_Panel {
	function init() {
		$this->title( 'Timestack' );

		$this->timestack = Timestack::get_instance();

		$url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, dirname( dirname( __FILE__ ) ) );

		wp_enqueue_style( 'timestack-debug-bar', $url . '/css/timestack-debug-bar.css' );
	}

	function prerender() {

		$this->set_visible( true );
	}

	function render() {
		$this->timestack->stack->force_end_operation();
		?>
		
		<?php $this->render_split_timeline( $this->timestack->stack ) ?>
		<script>
			jQuery( '.timestack-split-timeline > li' ).click( function( e ) {
				e.preventDefault();
				e.stopPropagation();

				if ( e.target.nodeName !== 'LI' ) {
					return;
				}

				if ( jQuery( this ).hasClass( 'unknown-time' ) || jQuery( this ).hasClass( 'active' ) ) {
					jQuery( this ).removeClass( 'active' );
					jQuery( this ).siblings( '.active' ).removeClass( 'active' );
					jQuery( this ).closest( '.timestack-split-timeline' ).removeClass( 'selected-item' );
					return;
				}

				jQuery( this ).siblings( '.active' ).removeClass( 'active' );
				jQuery( this ).addClass( 'active' );
				jQuery( this ).closest( '.timestack-split-timeline' ).addClass( 'selected-item' );
			} );

			jQuery( '.toggle-operation-details' ).click( function(e) {
				e.preventDefault();
				jQuery( this ).closest( '.timestack-split-timeline-header' ).find( '.timestack-split-timeline-details' ).slideToggle( 'fast' );
			});
		</script>
		<?php
	}

	private function render_split_timeline( Timestack_Operation $operation ) {

		$previous_child = null;
		$total_percent = 0;

		?>
		<div class="timestack-timeline">
			<div class="timestack-split-timeline-header">
				<?php if ( $operation->get_label() !== 'wp' ) : ?>
					<h5><?php echo $operation->get_label(); ?> [<a class="toggle-operation-details" href="#">Details</a>]</h5>
				<?php endif ?>

				<div class="timestack-split-timeline-details">
					<?php $this->render_vars_table( $operation->get_vars() ) ?>
				</div>

				<span class="total-time"><?php echo number_format( $operation->duration * 1000 ) ?> ms</span>
			</div>
			
			<ul class="timestack-split-timeline">
				<?php foreach ( $operation->children as $child_operation ) :

					if ( $previous_child && $operation->duration) {
						$unknown_time_percent = 100 / $operation->duration * ( $child_operation->start_time - $previous_child->end_time );
					} else if ( $operation->duration ) {
						$unknown_time_percent = 100 / $operation->duration * ( $child_operation->start_time - $operation->start_time );
					} else {
						$unknown_time_percent = 0;
					}

					if ( $child_operation->duration && $operation->duration ) {
						$percent_time = 100 / $operation->duration * $child_operation->duration;
					} else {
						$percent_time = 0;
					}
					
					$this->render_unknown_timeline_block( $unknown_time_percent );
					$this->render_timeline_block( $percent_time, $child_operation );
					$previous_child = $child_operation; 

				endforeach;

				if ( $child_operation && $child_operation->end_time ) {
					$unknown_time_percent = 100 / $operation->duration * ( $operation->end_time - $child_operation->end_time );
				} else {
					$unknown_time_percent = 0;
				}

				$this->render_unknown_timeline_block( $unknown_time_percent );
				?>
			</ul>
		</div>
		<?php
	}

	private function render_timeline_block( $percent, Timestack_Operation $operation ) {

		if ( is_a( $operation, 'Timestack_Event' ) ) {
			$type = 'event';
		} else {
			$type = 'operation';
		}
		?>

		<?php if ( $type == 'operation' ) : ?>
			<li class="type-<?php echo esc_attr( $type ) ?>" title="<?php echo esc_attr( $operation->get_label() ) ?>&#013;Time: <?php echo number_format( $operation->duration * 1000 ); ?> ms&#013;Start: <?php echo number_format( $operation->time * 1000 ); ?> ms" style="width: <?php echo floatval( $percent ); ?>%; background-color: <?php echo esc_attr( $this->string_to_color( $operation->get_label() ) ) ?>;">
				<?php
				if ( $operation->children ) {
					$this->render_split_timeline( $operation );
				}
				?>
			</li>
		<?php else : ?>
			<li class="type-<?php echo esc_attr( $type ) ?>" title="<?php echo esc_attr( $operation->get_label() ) ?>&#013;Start: <?php echo number_format( $operation->time * 1000 ); ?> ms">
					
			</li>
		<?php endif ?>
		<?php
	}  

	private function render_unknown_timeline_block( $percent ) {
		?>
		<li class="unknown-time" style="width: <?php echo floatval( $percent ) ?>%">
					
		</li>
		<?php
	}

	public function string_to_color( $str ) {
		$code = dechex(crc32($str));
		$code = substr($code, 0, 6);
		return '#' . $code;
	}

	private function render_vars_table( $data ) {

		$data = (array) $data;
		?>
		<table>
			<?php foreach ( $data as $key => $value ) : ?>
				<tr>
					<td><?php echo esc_html( $key ) ?></td>
					<td>
						<?php if ( is_bool( $value ) || $value === "" ) : ?>
							<pre><?php var_dump( $value ) ?></pre>
						<?php elseif ( is_scalar( $value ) ) : ?>
							<pre><?php echo( $value ) ?></pre>
						<?php elseif ( is_object( $value ) || is_array( $value ) ) : ?>
							<?php $this->render_vars_table( $value ); ?>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
		</table>
		<?php
	}
}
