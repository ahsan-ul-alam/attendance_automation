<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>HRMS — Daily Attendance</title>
  <!-- Tailwind v4 (Browser CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    /* Smooth scroll + hide number spinners */
    html { scroll-behavior: smooth; }
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type="number"] { -moz-appearance: textfield; }
    /* Sticky table header */
    thead th { position: sticky; top: 0; z-index: 10; }
  </style>
</head>

<body class="min-h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  @php
    // Quick derived stats (safe if $rows is a Collection of Attendance models)
    $present = $rows->where('status','Present')->count();
    $late    = $rows->where('status','Late')->count();
    $half    = $rows->where('status','Half')->count();
    $leave   = $rows->where('status','Leave')->count();
    $holiday = $rows->where('status','Holiday')->count();
    $absent  = $rows->where('status','Absent')->count();
    $total   = max(1, $rows->count());

    $statusClasses = [
      'Present' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800',
      'Late'    => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:ring-amber-800',
      'Half'    => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-200 dark:ring-slate-700',
      'Leave'   => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-900/40 dark:text-sky-200 dark:ring-sky-800',
      'Holiday' => 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-200 dark:ring-indigo-800',
      'Absent'  => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:ring-rose-800',
    ];
  @endphp

  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    <!-- Top Bar -->
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Daily Attendance</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Date: <span class="font-medium">{{ $date }}</span></p>
      </div>

      <!-- Filters -->
      <form method="GET" class="w-full md:w-auto">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <div class="col-span-1">
            <label class="block text-sm font-medium mb-1">Date</label>
            <input type="date" name="date" value="{{ request('date', $date) }}"
                   class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700" />
          </div>
          <div class="col-span-1">
            <label class="block text-sm font-medium mb-1">Employee</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Search name or code…"
                   class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700" />
          </div>
          <div class="col-span-1">
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status"
                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700">
              <option value="">All</option>
              @foreach(['Present','Late','Half','Leave','Holiday','Absent'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-span-1 flex gap-2">
            <button class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
              Apply
            </button>
            <a href="{{ url()->current() }}"
               class="inline-flex items-center justify-center rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 shadow hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">Reset</a>
          </div>
        </div>
      </form>
    </div>

    <!-- Summary Cards -->
    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      @php
        $cards = [
          ['label'=>'Present','value'=>$present,'color'=>'bg-emerald-50 dark:bg-emerald-950/30','ring'=>'ring-emerald-200/60'],
          ['label'=>'Late','value'=>$late,'color'=>'bg-amber-50 dark:bg-amber-950/30','ring'=>'ring-amber-200/60'],
          ['label'=>'Half','value'=>$half,'color'=>'bg-slate-50 dark:bg-slate-800/60','ring'=>'ring-slate-200/60'],
          ['label'=>'Leave','value'=>$leave,'color'=>'bg-sky-50 dark:bg-sky-950/30','ring'=>'ring-sky-200/60'],
          ['label'=>'Holiday','value'=>$holiday,'color'=>'bg-indigo-50 dark:bg-indigo-950/30','ring'=>'ring-indigo-200/60'],
          ['label'=>'Absent','value'=>$absent,'color'=>'bg-rose-50 dark:bg-rose-950/30','ring'=>'ring-rose-200/60'],
        ];
      @endphp
      @foreach($cards as $c)
        <div class="rounded-2xl {{ $c['color'] }} ring-1 {{ $c['ring'] }} p-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ $c['label'] }}</p>
          <p class="mt-1 text-2xl font-semibold">{{ $c['value'] }}</p>
          <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-white/60 dark:bg-gray-700/60">
            @php $pct = round(($c['value'] / $total) * 100); @endphp
            <div class="h-full bg-gray-900/80 dark:bg-white/80" style="width: {{ $pct }}%"></div>
          </div>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $pct }}% </p>
        </div>
      @endforeach
    </div>

    <!-- Actions -->
    <div class="mt-6 flex flex-wrap items-center gap-2">
      <button onclick="window.print()"
        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
        <!-- print icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 9V4h12v5h2a2 2 0 0 1 2 2v6h-4v3H8v-3H4v-6a2 2 0 0 1 2-2h0Zm2-3h8v3H8V6Zm8 11H8v1h8v-1Zm-9-5h2v2H7v-2Zm4 0h2v2h-2v-2Z"/></svg>
        Print
      </button>
      {{-- {{ route('attendance.csv', ['date'=>request('date',$date),'q'=>request('q'),'status'=>request('status')]) }} --}}
      <a href=""
         class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
        <!-- download icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.586l3.293-3.293 1.414 1.414L12 17.414l-4.707-4.707 1.414-1.414L12 13.586V3h0ZM5 19h14v2H5v-2Z"/></svg>
        Export CSV
      </a>
      <button id="toggleTheme"
        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
        🌙/☀️ Theme
      </button>
    </div>

    <!-- Desktop Table -->
    <div class="mt-4 hidden md:block overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
      <div class="overflow-auto max-h-[70vh]">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100/80 backdrop-blur dark:bg-gray-700/50">
            <tr class="text-left text-gray-600 dark:text-gray-200">
              <th class="px-4 py-3 font-semibold">Employee</th>
              <th class="px-4 py-3 font-semibold">Code</th>
              <th class="px-4 py-3 font-semibold">Date</th>
              <th class="px-4 py-3 font-semibold">In</th>
              <th class="px-4 py-3 font-semibold">Out</th>
              <th class="px-4 py-3 font-semibold">Work (h)</th>
              <th class="px-4 py-3 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($rows as $r)
              <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40">
                <td class="px-4 py-3">
                  <div class="font-medium">{{ $r->employee->name }}</div>
                  <div class="text-xs text-gray-500">{{ $r->employee->employee_code }}</div>
                </td>
                <td class="px-4 py-3">{{ $r->employee->employee_code }}</td>
                <td class="px-4 py-3">{{ $r->date->toDateString() }}</td>
                <td class="px-4 py-3">{{ optional($r->in_time)->format('H:i') }}</td>
                <td class="px-4 py-3">{{ optional($r->out_time)->format('H:i') }}</td>
                <td class="px-4 py-3">{{ number_format($r->work_minutes/60, 2) }}</td>
                <td class="px-4 py-3">
                  @php $cls = $statusClasses[$r->status] ?? 'bg-gray-100 text-gray-700 ring-1 ring-gray-200'; @endphp
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $cls }}">
                    {{ $r->status }}
                  </span>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No records</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden mt-4 space-y-3">
      @forelse($rows as $r)
        @php $cls = $statusClasses[$r->status] ?? 'bg-gray-100 text-gray-700 ring-1 ring-gray-200'; @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-base font-semibold">{{ $r->employee->name }}</p>
              <p class="text-xs text-gray-500">Code: {{ $r->employee->employee_code }}</p>
            </div>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $cls }}">{{ $r->status }}</span>
          </div>
          <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
            <div>
              <p class="text-gray-500">Date</p>
              <p class="font-medium">{{ $r->date->toDateString() }}</p>
            </div>
            <div>
              <p class="text-gray-500">In</p>
              <p class="font-medium">{{ optional($r->in_time)->format('H:i') }}</p>
            </div>
            <div>
              <p class="text-gray-500">Out</p>
              <p class="font-medium">{{ optional($r->out_time)->format('H:i') }}</p>
            </div>
          </div>
          <div class="mt-3">
            <p class="text-gray-500 text-sm">Work (h)</p>
            <p class="font-medium">{{ number_format($r->work_minutes/60, 2) }}</p>
          </div>
        </div>
      @empty
        <p class="text-center text-gray-500">No records</p>
      @endforelse
    </div>

    <!-- Pagination placeholder (if you paginate $rows) -->
    {{-- <div class="mt-6">{{ $rows->links() }}</div> --}}
  </div>

  <script>
    // Simple theme toggle (localStorage)
    const btn = document.getElementById('toggleTheme');
    const root = document.documentElement;
    const key = 'hrms-theme';
    const saved = localStorage.getItem(key);
    if (saved === 'dark') root.classList.add('dark');
    btn?.addEventListener('click', () => {
      root.classList.toggle('dark');
      localStorage.setItem(key, root.classList.contains('dark') ? 'dark' : 'light');
    });
  </script>
</body>
</html>
