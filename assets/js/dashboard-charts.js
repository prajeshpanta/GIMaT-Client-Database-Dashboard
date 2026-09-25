/**
 * GIMaT Dashboard — Dashboard Analytics charts (Chart.js).
 *
 * Data comes from `gimatChartData`, localized server-side in
 * includes/class-gimat-admin.php from Gimat_Records::chart_data(), which is
 * queried live from wp_gimat_records / wp_gimat_events on every page load.
 * There is nothing hardcoded here — add, edit, or delete a Record/Event and
 * refresh the Dashboard to see it reflected.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	if ( typeof Chart === 'undefined' || typeof gimatChartData === 'undefined' ) {
		return;
	}

	var palette = {
		primary: '#4f46e5',
		primaryLight: '#818cf8',
		success: '#059669',
		instructor: '#a21caf',
		dalit: '#f43f5e',
		janajati: '#f59e0b',
		other: '#10b981',
		male: '#3b82f6',
		female: '#ec4899',
		grid: 'rgba(17,24,39,0.06)',
		text: '#6b7280'
	};

	var noDataText = ( gimatChartData.i18n && gimatChartData.i18n.noData ) ? gimatChartData.i18n.noData : 'No data yet.';

	if ( Chart.defaults && Chart.defaults.font ) {
		Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
	}
	Chart.defaults.color = palette.text;

	function showEmpty( canvasId ) {
		var canvas = document.getElementById( canvasId );
		if ( ! canvas ) {
			return;
		}
		var wrap = canvas.parentNode;
		if ( wrap ) {
			wrap.innerHTML = '<p class="gimat-chart-empty">' + noDataText + '</p>';
		}
	}

	function barGrid( axisColor ) {
		return {
			color: axisColor,
			drawBorder: false
		};
	}

	// 1. Participants by Month — Bar chart.
	( function () {
		var el = document.getElementById( 'gimat-chart-monthly' );
		var rows = gimatChartData.monthly || [];
		if ( ! el ) {
			return;
		}
		if ( ! rows.length ) {
			showEmpty( 'gimat-chart-monthly' );
			return;
		}
		new Chart( el.getContext( '2d' ), {
			type: 'bar',
			data: {
				labels: rows.map( function ( r ) { return r.label; } ),
				datasets: [ {
					label: 'Participants',
					data: rows.map( function ( r ) { return r.total; } ),
					backgroundColor: palette.primaryLight,
					hoverBackgroundColor: palette.primary,
					borderRadius: 6,
					maxBarThickness: 36
				} ]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: { padding: 10, cornerRadius: 8 }
				},
				scales: {
					x: { grid: { display: false } },
					y: { beginAtZero: true, grid: barGrid( palette.grid ), ticks: { precision: 0 } }
				}
			}
		} );
	} )();

	// 2. Participant Demographics (Dalit / Janajati / Other) — Doughnut chart.
	( function () {
		var el = document.getElementById( 'gimat-chart-demographics' );
		var d = gimatChartData.demographics || { dalit: 0, janajati: 0, other: 0 };
		var total = ( d.dalit || 0 ) + ( d.janajati || 0 ) + ( d.other || 0 );
		if ( ! el ) {
			return;
		}
		if ( ! total ) {
			showEmpty( 'gimat-chart-demographics' );
			return;
		}
		new Chart( el.getContext( '2d' ), {
			type: 'doughnut',
			data: {
				labels: [ 'Dalit', 'Janajati', 'Other' ],
				datasets: [ {
					data: [ d.dalit, d.janajati, d.other ],
					backgroundColor: [ palette.dalit, palette.janajati, palette.other ],
					borderWidth: 0,
					hoverOffset: 6
				} ]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '68%',
				plugins: {
					legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } },
					tooltip: { padding: 10, cornerRadius: 8 }
				}
			}
		} );
	} )();

	// 3. Gender Distribution (Male / Female) — Doughnut chart, kept separate
	// from the Dalit/Janajati/Other Demographics chart above.
	( function () {
		var el = document.getElementById( 'gimat-chart-gender' );
		var g = gimatChartData.gender || { male: 0, female: 0 };
		var total = ( g.male || 0 ) + ( g.female || 0 );
		if ( ! el ) {
			return;
		}
		if ( ! total ) {
			showEmpty( 'gimat-chart-gender' );
			return;
		}
		new Chart( el.getContext( '2d' ), {
			type: 'doughnut',
			data: {
				labels: [ 'Male', 'Female' ],
				datasets: [ {
					data: [ g.male, g.female ],
					backgroundColor: [ palette.male, palette.female ],
					borderWidth: 0,
					hoverOffset: 6
				} ]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '68%',
				plugins: {
					legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } },
					tooltip: { padding: 10, cornerRadius: 8 }
				}
			}
		} );
	} )();

	// 4. Top Locations — Horizontal bar chart.
	( function () {
		var el = document.getElementById( 'gimat-chart-locations' );
		var rows = gimatChartData.top_locations || [];
		if ( ! el ) {
			return;
		}
		if ( ! rows.length ) {
			showEmpty( 'gimat-chart-locations' );
			return;
		}
		new Chart( el.getContext( '2d' ), {
			type: 'bar',
			data: {
				labels: rows.map( function ( r ) { return r.label; } ),
				datasets: [ {
					label: 'Sessions',
					data: rows.map( function ( r ) { return r.sessions; } ),
					backgroundColor: palette.success,
					borderRadius: 6,
					maxBarThickness: 22
				} ]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: { padding: 10, cornerRadius: 8 }
				},
				scales: {
					x: { beginAtZero: true, grid: barGrid( palette.grid ), ticks: { precision: 0 } },
					y: { grid: { display: false } }
				}
			}
		} );
	} )();

	// 5. Top Instructors — Horizontal bar chart.
	( function () {
		var el = document.getElementById( 'gimat-chart-instructors' );
		var rows = gimatChartData.top_instructors || [];
		if ( ! el ) {
			return;
		}
		if ( ! rows.length ) {
			showEmpty( 'gimat-chart-instructors' );
			return;
		}
		new Chart( el.getContext( '2d' ), {
			type: 'bar',
			data: {
				labels: rows.map( function ( r ) { return r.label; } ),
				datasets: [ {
					label: 'Sessions',
					data: rows.map( function ( r ) { return r.sessions; } ),
					backgroundColor: palette.instructor,
					borderRadius: 6,
					maxBarThickness: 22
				} ]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: { padding: 10, cornerRadius: 8 }
				},
				scales: {
					x: { beginAtZero: true, grid: barGrid( palette.grid ), ticks: { precision: 0 } },
					y: { grid: { display: false } }
				}
			}
		} );
	} )();
} );
