document.addEventListener( 'DOMContentLoaded', function () {
	// Add/remove dynamic custom field rows on the record form.
	var wrap = document.getElementById( 'gimat-custom-fields-wrap' );
	var addBtn = document.getElementById( 'gimat-add-custom-field' );
	var template = document.getElementById( 'gimat-custom-field-template' );

	function syncEmptyMessage() {
		if ( ! wrap ) {
			return;
		}
		var rows = wrap.querySelectorAll( '.gimat-custom-field-row' );
		var msg = document.getElementById( 'gimat-no-custom-fields-msg' );
		if ( rows.length === 0 && ! msg ) {
			var p = document.createElement( 'p' );
			p.className = 'gimat-no-custom-fields';
			p.id = 'gimat-no-custom-fields-msg';
			p.textContent = 'No custom fields. Click "Add Field" to add one.';
			wrap.appendChild( p );
		} else if ( rows.length > 0 && msg ) {
			msg.remove();
		}
	}

	if ( addBtn && wrap && template ) {
		addBtn.addEventListener( 'click', function () {
			var msg = document.getElementById( 'gimat-no-custom-fields-msg' );
			if ( msg ) {
				msg.remove();
			}
			var clone = template.content.cloneNode( true );
			wrap.appendChild( clone );
		} );
	}

	if ( wrap ) {
		wrap.addEventListener( 'click', function ( e ) {
			if ( e.target && e.target.closest( '.gimat-remove-custom-field' ) ) {
				e.target.closest( '.gimat-custom-field-row' ).remove();
				syncEmptyMessage();
			}
		} );
	}

	// Toggle inline "edit event" forms on the Events page.
	document.querySelectorAll( '.gimat-event-row' ).forEach( function ( row ) {
		var editBtn   = row.querySelector( '.gimat-edit-event-btn' );
		var cancelBtn = row.querySelector( '.gimat-cancel-edit-event' );
		var display   = row.querySelector( '.gimat-event-name-display' );
		var form      = row.querySelector( '.gimat-event-edit-form' );

		if ( editBtn && form && display ) {
			editBtn.addEventListener( 'click', function () {
				display.style.display = 'none';
				form.style.display = 'flex';
				var input = form.querySelector( 'input[name="event_name"]' );
				if ( input ) {
					input.focus();
					input.select();
				}
			} );
		}
		if ( cancelBtn && form && display ) {
			cancelBtn.addEventListener( 'click', function () {
				form.style.display = 'none';
				display.style.display = '';
			} );
		}
	} );

	// Toggle the CSV import panel on the Records page.
	var openImport = document.getElementById( 'gimat-open-import' );
	var importPanel = document.getElementById( 'gimat-import-panel' );
	if ( openImport && importPanel ) {
		openImport.addEventListener( 'click', function () {
			importPanel.style.display = importPanel.style.display === 'none' ? 'block' : 'none';
		} );
	}

	// Auto-dismiss the compact notice pill shown next to the page title
	// (Events / Add Record / All Records). No close button by design — it
	// just fades out on its own after the delay set server-side in
	// data-gimat-autodismiss (2.5s for success, longer for warning/error).
	// If it has an expandable "View skipped rows" section and the person
	// has it open, the timer is paused until they close it again.
	document.querySelectorAll( '.gimat-notice-pill[data-gimat-autodismiss]' ).forEach( function ( pill ) {
		var delay = parseInt( pill.getAttribute( 'data-gimat-autodismiss' ), 10 ) || 0;
		if ( ! delay ) {
			return;
		}
		var timer = null;

		function dismiss() {
			if ( ! pill.parentNode ) {
				return;
			}
			pill.classList.add( 'gimat-notice-dismiss' );
			setTimeout( function () {
				if ( pill.parentNode ) {
					pill.parentNode.removeChild( pill );
				}
			}, 300 );
		}

		function schedule( ms ) {
			clearTimeout( timer );
			timer = setTimeout( dismiss, ms );
		}

		schedule( delay );

		var details = pill.querySelector( 'details' );
		if ( details ) {
			details.addEventListener( 'toggle', function () {
				if ( details.open ) {
					clearTimeout( timer ); // reading the list — don't yank it away
				} else {
					schedule( 3000 ); // give a beat after closing, then dismiss
				}
			} );
		}
	} );
		// ---------- Dark Mode ----------
	var wrapper = document.querySelector( '.gimat-wrap' );
	var toggle  = document.getElementById( 'gimat-dark-toggle' );

	if ( wrapper && toggle ) {
		var icon = toggle.querySelector( '.gimat-dark-icon' );

		// Load saved preference
		if ( localStorage.getItem( 'gimat_dark_mode' ) === '1' ) {
			wrapper.classList.add( 'gimat-dark' );
			if ( icon ) icon.textContent = '☀️';
		}

		toggle.addEventListener( 'click', function () {
			wrapper.classList.toggle( 'gimat-dark' );
			var isDark = wrapper.classList.contains( 'gimat-dark' );
			localStorage.setItem( 'gimat_dark_mode', isDark ? '1' : '0' );
			if ( icon ) icon.textContent = isDark ? '☀️' : '🌙';
		} );
	}

	// ---------- Prevent double-submit on Add/Edit Record ----------
	// A fast double-click (or double-tap Enter) can fire the form's submit
	// event twice before the page navigates away, which would otherwise
	// send two near-simultaneous "Add Record" requests. Disabling the
	// button the instant the form submits stops the second click from
	// doing anything, closing this off at the source (the server-side
	// duplicate lock in Gimat_Records::insert()/update() covers the rest).
	var recordForm = document.querySelector( '.gimat-record-form' );
	if ( recordForm ) {
		recordForm.addEventListener( 'submit', function () {
			var submitBtn = recordForm.querySelector( 'button[type="submit"]' );
			if ( submitBtn && ! submitBtn.disabled ) {
				submitBtn.disabled = true;
				submitBtn.classList.add( 'gimat-btn-saving' );
			}
		} );
	}
} );
