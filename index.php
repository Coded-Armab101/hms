<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bradith | Hospital Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        .hero{
            background: url(https://img.freepik.com/premium-photo/diverse-group-four-doctors-healthcare-workers-studying-x-ray-hospital-corridor_13339-330214.jpg?semt=ais_hybrid&w=740&q=80);
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <header class="sticky top-0 z-50 glass-effect border-b border-slate-200 shadow-sm">
        <div class=" mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <div class="bg-black p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-10V4m0 10V4m-4 12h4m1 5h2m0 0h5m-2 0h-5"></path></svg>
                    </div>
                    <span class="text-xl font-bold text-black tracking-tight">Bradith HMS</span>
                </div>
                
                <nav class="hidden md:flex space-x-1">
                    <a href="index.php" class="px-3 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">Home</a>
                    <a href="backend/admin/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition">Admin</a>
                    <a href="backend/record/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition">Records</a>
                    <a href="backend/doc/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition">Doctor</a>
                    <a href="backend/nurse/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition">Nurse</a>
                    <a href="backend/lab/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition">Lab</a>
                    <a href="backend/pharmacy/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition">Pharmacy</a>
                </nav>
            </div>
        </div>
    </header>

    <main class=" m-2 p-2">

        <section class="m-4 rounded-md  text-white overflow-hidden shadow-xl hero">
            <div class="px-8 py-16 md:py-24 md:px-16 flex flex-col md:flex-row items-center">
                <div class="md:w-2/3">
                    <span class="inline-block px-3 py-1 bg-blue-500/30 rounded-full text-xs font-semibold uppercase tracking-wider mb-4">Advanced Healthcare Management</span>
                    <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-6">
                        Integrated Care for a <br><span class="text-blue-300">Healthier Community.</span>
                    </h1>
                    <p class="text-lg text-blue-100 mb-8 max-w-xl leading-relaxed">
                        Streamlining clinical workflows, patient records, and diagnostics. Our centralized system empowers medical professionals to deliver precision care with compassionate efficiency.
                    </p>
                    <div class="flex gap-4">
                        <a href="#portals" class="bg-white text-blue-700 px-6 py-3 rounded-lg font-bold hover:bg-blue-50 transition shadow-lg">Access Portals</a>
                        <a href="#" class="border border-blue-300 px-6 py-3 rounded-lg font-semibold hover:bg-blue-600 transition">System Overview</a>
                    </div>
                </div>
                <div class="hidden md:block md:w-1/3 opacity-20">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
                </div>
            </div>
        </section>

        <section id="portals" class="m-4 p-8 rounded-md bg-white border border-slate-200 shadow-sm">
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-bold text-slate-900">Medical Departments</h2>
                <p class="text-slate-500 mt-2">Select a specialized module to manage patient flow and clinical data.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 border border-slate-100 rounded-xl hover:border-blue-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 mb-4 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Patient Records</h3>
                    <p class="text-slate-600 text-sm mb-4">Registration, appointment scheduling, and primary health record management.</p>
                    <a href="backend/record/index.php" class="text-blue-600 font-semibold text-sm hover:underline">Launch Records →</a>
                </div>

                <div class="p-6 border border-slate-100 rounded-xl hover:border-blue-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center text-purple-600 mb-4 group-hover:bg-purple-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Laboratory</h3>
                    <p class="text-slate-600 text-sm mb-4">Track pathology requests, specimen processing, and digital result reporting.</p>
                    <a href="backend/lab/index.php" class="text-purple-600 font-semibold text-sm hover:underline">Launch Lab →</a>
                </div>

                <div class="p-6 border border-slate-100 rounded-xl hover:border-blue-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 mb-4 group-hover:bg-emerald-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Pharmacy</h3>
                    <p class="text-slate-600 text-sm mb-4">Inventory tracking, prescription fulfillment, and medication history.</p>
                    <a href="backend/pharmacy/index.php" class="text-emerald-600 font-semibold text-sm hover:underline">Launch Pharmacy →</a>
                </div>
            </div>
        </section>

        <section class="m-4 p-10 rounded-md bg-slate-900 text-white">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl font-bold text-blue-400">24/7</div>
                    <div class="text-slate-400 text-sm uppercase">Availability</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-400">100%</div>
                    <div class="text-slate-400 text-sm uppercase">Secure Data</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-400">Real-time</div>
                    <div class="text-slate-400 text-sm uppercase">Diagnostics</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-400">HIPAA</div>
                    <div class="text-slate-400 text-sm uppercase">Compliant</div>
                </div>
            </div>
        </section>

    </main>

    <footer class="text-center py-8 text-slate-500 text-sm">
        &copy; 2026 Bradith Hospital Management System. All rights reserved.
    </footer>

</body>
</html>