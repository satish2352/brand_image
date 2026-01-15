@extends('superadm.layout.master')

@section('content')
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between mb-3">
                <h4>Revenue Graph (Month-wise)</h4>

                <form method="GET" class="d-flex">
                    <select name="year" class="form-control me-2" onchange="this.form.submit()">
                        @for ($y = 2024; $y <= now()->year; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </form>
            </div>

            <canvas id="revenueChart" height="100"></canvas>

            <p class="text-muted mt-3">
                <small>Only PAID bookings included</small>
            </p>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($months),
                datasets: [{
                    label: 'Revenue (₹)',
                    data: @json($revenues),
                    backgroundColor: '#4e73df',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return '₹ ' + ctx.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₹ ' + value
                        }
                    }
                }
            }
        });
    </script>
@endsection
