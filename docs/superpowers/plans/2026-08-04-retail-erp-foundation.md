# Retail ERP Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menghasilkan fondasi ERP retail multi-company yang dapat dipasang pada PHP 8.2/PostgreSQL, dengan aturan posting immutable, approval, pricing customer, purchasing, inventory, accounting, integrasi penjualan, dan UI responsif.

**Architecture:** Laravel 12 modular monolith dengan domain service murni PHP untuk aturan bisnis dan adapter Laravel untuk persistence/HTTP. Semua posting memakai transaksi database dan journal balancing; PostgreSQL trigger menjadi pengaman immutable tambahan.

**Tech Stack:** PHP 8.2, Laravel 12, PostgreSQL 15+, Blade, CSS responsif, PHPUnit/Pest-compatible tests, Docker Compose.

## Global Constraints
- Bahasa UI: Indonesia.
- Database: PostgreSQL saja.
- Dokumen posted tidak boleh diedit atau dihapus.
- Koreksi memakai adjustment/reversal/dokumen baru.
- Posting operasional dan accounting harus atomik.
- Price level melekat pada customer.
- Harga tanpa price level aktif harus diblokir.
- Perubahan harga disetujui CEO per baris dan tidak boleh backdate.

---

### Task 1: Domain Rules
**Files:** tests/Domain/*, app/Domain/*
- [ ] Tulis test gagal untuk approval matrix, price activation, customer price resolver, immutable posting, balanced journal, dan three-way matching.
- [ ] Jalankan test dan pastikan gagal karena class belum tersedia.
- [ ] Implementasikan class domain minimal.
- [ ] Jalankan test sampai semua lulus.

### Task 2: Laravel Skeleton and Infrastructure
**Files:** composer.json, artisan, bootstrap/*, config/*, routes/*, Dockerfile, docker-compose.yml
- [ ] Buat skeleton Laravel 12 dan konfigurasi PHP 8.2/PostgreSQL.
- [ ] Tambahkan environment example dan script instalasi.
- [ ] Validasi seluruh file PHP dengan `php -l`.

### Task 3: Database Schema and Immutability
**Files:** database/migrations/*, database/seeders/*
- [ ] Buat migration organisasi, master, approval, purchasing, inventory, accounting, integration, audit, dan reporting.
- [ ] Buat PostgreSQL function/trigger penolak mutasi dokumen posted.
- [ ] Tambahkan seed role, price level, COA contoh, dan posting profile.
- [ ] Validasi sintaks PHP migration.

### Task 4: Application Services and API
**Files:** app/Application/*, app/Http/Controllers/*, app/Models/*, routes/api.php
- [ ] Implementasikan posting engine dan handler transaksi inti.
- [ ] Implementasikan price change workflow dan scheduler activation command.
- [ ] Implementasikan sales integration idempotent dan exception queue.
- [ ] Tambahkan request validation dan policy boundary.

### Task 5: Responsive Indonesian UI
**Files:** resources/views/*, public/css/erp.css, public/js/erp.js, routes/web.php
- [ ] Buat app shell responsif.
- [ ] Buat dashboard, price approval, purchasing, accounting, integration exception, dan report builder.
- [ ] Pastikan tabel memiliki mobile layout dan empty state.

### Task 6: Verification and Packaging
**Files:** README.md, docs/*
- [ ] Jalankan domain tests.
- [ ] Jalankan `php -l` pada seluruh PHP source.
- [ ] Periksa tidak ada placeholder/TODO kritikal.
- [ ] Buat arsip ZIP untuk handoff.
