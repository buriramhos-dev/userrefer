<?php
// หน้านี้ทำเหมือน user.php แต่ไม่แสดง ชื่อ และ นามสกุล
require_once __DIR__ . '/db.php';

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Fetch rows (exclude name and surname from SELECT since we don't show them)
$allRows = $pdo->query("
    SELECT id, date_in, gender, ward, hospital, o2_ett_icd, partner, note,
           time_contact AS contact_time, status
    FROM `{$tableName}`
    ORDER BY status ASC, date_in DESC, id DESC
")->fetchAll();

// Group rows by status
$groupedRows = [1 => [], 2 => [], 3 => []];
foreach ($allRows as $row) {
    $status = (int)$row['status'];
    if (isset($groupedRows[$status])) $groupedRows[$status][] = $row;
}

$statusLabels = [1 => 'รอรถเข้ารับ', 2 => 'รถกำลังมารับ', 3 => 'บุรีรัมย์ไปส่ง'];

// Fetch zipcode helpers (same as user.php)
$zipcodeRows = $pdo->query("SELECT hospital_name, zipcode FROM hospital_zipcodes WHERE zipcode IS NOT NULL AND zipcode <> ''")->fetchAll();
$zipcodeMap = [];
foreach ($zipcodeRows as $zipRow) {
    $zip = trim($zipRow['zipcode']);
    $hospitalName = trim($zipRow['hospital_name']);
    if ($zip === '' || $hospitalName === '') continue;
    if (!isset($zipcodeMap[$zip])) $zipcodeMap[$zip] = [];
    $zipcodeMap[$zip][] = $hospitalName;
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* สไตล์หลัก */
        * {
            box-sizing: border-box;
        }
        /* Top-level media query for ultra-wide displays (>= 3400px) */
        @media (min-width: 3400px) {
            body { padding: 40px 0; font-size: 28px; }
            .container {
                max-width: none;
                width: 100%;
                padding: 60px 12px; /* tighter side padding so table has more room */
                border-radius: 20px;
            }
            .container img.bru-img { left: 48px; top: 48px; width: 180px; }
            .container img.Logo-img { left: 280px; top: 48px; width: 220px; }
            .container img.doctor-img { right: 280px; top: 48px; width: 260px; }
            .container img.ambulance-img { right: 48px; top: 48px; width: 260px; }
            h2 { font-size: 72px; margin: 56px 0 24px; text-align: center; }
            .toolbar { gap: 28px; margin-bottom: 40px; }
            #hospitalSearch, #statusFilter { font-size: 32px; padding: 20px 28px; border-radius: 16px; }
            .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            /* ลดขนาดและ padding เพื่อให้ข้อมูลพอดีกับทีวี 50" โดยไม่ต้องขึ้นบรรทัดใหม่ */
            table { width: 100%; margin-top: 3.5rem; table-layout: fixed; border-radius: 20px; min-width: 3200px; font-size: 26px; }
            th, td { white-space: nowrap !important; overflow: hidden; text-overflow: ellipsis; }
            th { font-size: 26px; padding: 12px 10px; font-weight: 700; }
            td { font-size: 26px; padding: 10px 10px; font-weight: 600; }
            th:nth-child(1), td:nth-child(1) { width: 10%; }
            th:nth-child(2), td:nth-child(2) { width: 6%; }
            th:nth-child(3), td:nth-child(3) { width: 13%; }
            th:nth-child(4), td:nth-child(4) { width: 28%; }
            th:nth-child(5), td:nth-child(5) { width: 28%; }
            th:nth-child(6), td:nth-child(6) { width: 12%; }
            th:nth-child(7), td:nth-child(7) { width: 15%; }
            th:nth-child(8), td:nth-child(8) { width: 11%; }
            th:nth-child(9), td:nth-child(9) { width: 150%; }
            tr.group-header td.group-header-cell { font-size: 40px; padding: 14px 14px; white-space: normal; overflow: visible; }
            .group-count { font-size: 28px; }
            .status-1, .status-2, .status-3 { font-size: 22px; padding: 8px 12px; }
        }

        :root{
            --page-bg: #d8eefe; /* requested background color */
            --panel-bg: #ffffff;
            --accent: #2d9bf7; /* bright blue */
            --accent-dark: #0b6ecf;
            --muted: #6b7280;
        }

        body {
            font-family: "Prompt", Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: var(--page-bg);
            min-height: 100vh;
            color: #0f1720;
            font-size: 14px;
        }

        .container {
            max-width: 95vw;
            width: 100%;
            margin: 0 auto;
            background: var(--panel-bg);
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 8px 30px rgba(2,6,23,0.06);
            position: relative;
        }
        .container img.bru-img {
            position: absolute;
            top: 32px;
            left: 70px;
            width: 50px;
            height: auto;
            opacity: 0.8;
            margin-top: 20px;
        }
      
        .container img.Logo-img {
            position: absolute;
            top: 32px;
            left: 180px;
            width: 70px;
            height: auto;
            opacity: 0.8;
            margin-top: 20px;
        }
        .container img.doctor-img {
            position: absolute;
            top: 30px;
            right: 200px;
            width: 80px;
            height: auto;
            opacity: 0.9;
            margin-top: 20px;
        }
        .container img.ambulance-img {
            position: absolute;
            top: 30px;
            right: 70px;
            width: 80px;
            height: auto;
            opacity: 0.9;
            margin-top: 20px;
        }
        h2 {
            margin-bottom: 20px;
            color: var(--accent-dark);
            font-weight: 700;
            font-size: 20px;
            text-align: center;
            margin: 50px;
        }

        


        /* Toolbar */
        .toolbar {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            animation: slideUp 0.5s ease;
            margin-top: 4rem;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        th {
           background: linear-gradient(135deg,  #55423d 0%, #6d554eff 100%);
            color: #fff;
            padding: 15px;
            font-weight: 600;
            text-align: left;
            font-size: 17px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th:first-child {
            border-top-left-radius: 12px;
        }

        th:last-child {
            border-top-right-radius: 12px;
        }

        /* Group Header Styles */
        tr.group-header {
            background: transparent;
        }

        tr.group-header td.group-header-cell {
            background: linear-gradient(135deg, #fda81fff 0%, #fda81fff  100%);
            color: #fff;
            font-size: 20px;
            font-weight: 100;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 2px solid rgba(255, 255, 255, 0.25);
            vertical-align: middle;
            line-height: 1.3;
        }
        tr.group-header.status-group-1 td.group-header-cell {
            background: linear-gradient(135deg, #3e8deeff 0%, #3e8deeff 100%);
        }

        tr.group-header.status-group-2 td.group-header-cell {
            background: linear-gradient(135deg, #fda81fff 0%, #fda81fff 100%);
        }

        tr.group-header.status-group-3 td.group-header-cell {
            background: linear-gradient(135deg, #06ba5dff 0%, #06ba5dff 100%);
        }
        .group-count {
            font-size: 20px;
            font-weight: 400;
            opacity: 0.9;
            margin-left: 10px;
        }

        td {
            padding: 15px;
            font-size: 17px;
            font-weight: 600;
            border-bottom: 1px solid #f0f0f0;
            background: #fff;
            vertical-align: top;
            transition: all 0.2s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background: linear-gradient(90deg, #e8faf9 0%, #fff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(78, 205, 196, 0.1);
        }

        /* Status Colors (ใช้สีเดิม) */
        .status-1 {
            color: #1f7aed;
            font-weight: 600;
            padding: 4px 12px;
            background: rgba(31, 122, 237, 0.1);
            border-radius: 20px;
            display: inline-block;
            font-size: 20px;
        }
       
        .status-2 {
            color: #f39c12;
            font-weight: 600;
            padding: 4px 12px;
            background: rgba(243, 156, 18, 0.1);
            border-radius: 20px;
            display: inline-block;
            font-size: 20px;
        }
        .status-3 {
            color: #00a651;
            font-weight: 600;
            padding: 4px 12px;
            background: rgba(0, 166, 81, 0.1);
            border-radius: 20px;
            display: inline-block;
            font-size: 20px;
        }
        


    </style>
</head>
<body>
<div class="container">
    <img src="img/Logo_of_Buriram_Hospital.png" alt="Logo" class="Logo-img">
    <img src="img/bru.png" alt="bru" class="bru-img">
    <img src="img/doctor.png" alt="doctor" class="doctor-img">
    <img src="img/ambulance.png" alt="ambulance" class="ambulance-img">
    <div class="page-header">   
        <h2>ผู้ป่วยรอส่งกลับไปรักษาต่อ
            </h2>   
    </div>
    


    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>วันที่</th>
                <th>เพศ</th>
                <th>ตึก</th>
                <th>โรงพยาบาล</th>
                <th>อุปกรณ์ที่ใช้</th>
                <th>พันธมิตร</th>
                <th>หมายเหตุ</th>
                <th>เวลาประสาน</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody id="dataTableBody">
        <?php
        $hasData = false;
        foreach ($groupedRows as $status => $rows) {
            if (!empty($rows)) {
                $hasData = true;
                ?>
                <tr class="group-header status-group-<?= $status ?>" data-group-status="<?= $status ?>">
                    <td colspan="9" class="group-header-cell">
                        <strong>กลุ่มที่ <?= $status ?>: <?= $statusLabels[$status] ?></strong>
                        <span class="group-count">(<?= count($rows) ?> รายการ)</span>
                    </td>
                </tr>
                <?php
                foreach ($rows as $r): ?>
                    <tr data-status="<?= e($r['status']) ?>">
                        <td data-label="DATE"><?= e($r['date_in']) ?></td>
                        <td data-label="GENDER"><?= ($r['gender'] === 'M') ? 'ชาย' : (($r['gender'] === 'F') ? 'หญิง' : '-') ?></td>
                        <td data-label="WARD"><?= e($r['ward']) ?></td>
                        <td data-label="HOSPITAL"><?= e($r['hospital']) ?></td>
                        <td data-label="O2/ETT/ICD"><?= e($r['o2_ett_icd']) ?></td>
                        <td data-label="พันธมิตร"><?= e($r['partner']) ?></td>
                        <td data-label="หมายเหตุ"><?= e($r['note']) ?></td>
                        <td data-label="เวลาประสาน"><?= e($r['contact_time']) ?></td>
                        <td data-label="สถานะ" class="status-<?= e($r['status']) ?>"><?= $statusLabels[$r['status']] ?? '-' ?></td>
                    </tr>
                <?php endforeach;
            }
        }
        if (!$hasData): ?>
            <tr><td colspan="9" style="text-align:center;">ไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
const zipcodeMap = <?= json_encode($zipcodeMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};
function getHospitalsFromZipInput(inputValue) {
    const filter = inputValue.trim().toLowerCase(); if (!filter) return null;
    const matches = [];
    Object.entries(zipcodeMap).forEach(([zip, hospitals]) => {
        if (!zip) return;
        if (zip.toLowerCase().startsWith(filter)) {
            (hospitals || []).forEach(name => {
                const lower = (name || '').toLowerCase(); if (lower && !matches.includes(lower)) matches.push(lower);
            });
        }
    });
    return matches.length ? matches : null;
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('hospitalSearch');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.getElementById('dataTableBody');
    const hospitalColumnIndex = 3; // date(0), gender(1), ward(2), hospital(3)

    // *** Auto-refresh Polling (3 seconds) ***
    let lastDataHash = null;
    setInterval(function() {
        fetch('api_get_data_user.php')
            .then(response => response.json())
            .then(json => {
                if (!json.success) return;

                const dataString = JSON.stringify(json.data);
                const currentHash = dataString.length; // Simple hash

                if (lastDataHash !== currentHash) {
                    lastDataHash = currentHash;
                    rebuildTableFromData(json.data);
                }
            })
            .catch(err => console.log('Polling error:', err));
    }, 3000); // 3 seconds

    // Helper: escape HTML (client-side)
    function e(v) {
        const div = document.createElement('div');
        div.textContent = v || '';
        return div.innerHTML;
    }

    // Function: rebuild table from API data (tailored for er.php columns)
    function rebuildTableFromData(allRows) {
        const groupedRows = { 1: [], 2: [], 3: [] };
        allRows.forEach(row => {
            const status = parseInt(row.status);
            if (groupedRows[status]) groupedRows[status].push(row);
        });

        const statusLabels = {
            1: 'รอรถเข้ารับ',
            2: 'รถกำลังมารับ',
            3: 'บุรีรัมย์ไปส่ง'
        };

        tableBody.innerHTML = '';
        let hasData = false;

        Object.entries(groupedRows).forEach(([status, rows]) => {
            if (rows.length > 0) {
                hasData = true;
                const headerTr = document.createElement('tr');
                headerTr.className = `group-header status-group-${status}`;
                headerTr.setAttribute('data-group-status', status);
                headerTr.innerHTML = `<td colspan="9" class="group-header-cell">
                    <strong>กลุ่มที่ ${status}: ${statusLabels[status]}</strong>
                    <span class="group-count">(${rows.length} รายการ)</span>
                </td>`;
                tableBody.appendChild(headerTr);

                rows.forEach(r => {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-status', r.status);
                    const genderText = r.gender === 'M' ? 'ชาย' : (r.gender === 'F' ? 'หญิง' : '-');
                    tr.innerHTML = `
                        <td data-label="DATE">${e(r.date_in)}</td>
                        <td data-label="GENDER">${genderText}</td>
                        <td data-label="WARD">${e(r.ward)}</td>
                        <td data-label="HOSPITAL">${e(r.hospital)}</td>
                        <td data-label="O2/ETT/ICD">${e(r.o2_ett_icd)}</td>
                        <td data-label="พันธมิตร">${e(r.partner)}</td>
                        <td data-label="หมายเหตุ">${e(r.note)}</td>
                        <td data-label="เวลาประสาน">${e(r.contact_time)}</td>
                        <td data-label="สถานะ" class="status-${e(r.status)}">${statusLabels[r.status] || '-'}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            }
        });

        if (!hasData) {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="9" style="text-align:center;">ไม่มีข้อมูล</td>';
            tableBody.appendChild(tr);
        }

        // Re-apply filters to the rebuilt table
        applyFilters();
    }

    function applyFilters() {
        const hospitalFilter = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        const rows = Array.from(tableBody.getElementsByTagName('tr'));
        const hospitalsFromZip = getHospitalsFromZipInput(hospitalFilter);

        // First: evaluate data rows and record which groups have visible rows
        const groupHasVisibleRows = {};
        rows.forEach(row => {
            if (row.classList.contains('group-header')) return; // skip headers for now

            // If this row is not a normal data row (e.g. placeholder), show it
            if (row.children.length < 9) { row.style.display = ''; return; }

            const hospitalCell = row.getElementsByTagName('td')[hospitalColumnIndex];
            const rowStatus = row.getAttribute('data-status');
            if (!hospitalCell) { row.style.display = 'none'; return; }

            const hospitalText = (hospitalCell.textContent || hospitalCell.innerText).toLowerCase();

            // Status filter: if a status is selected and this row doesn't match, hide it
            if (selectedStatus && rowStatus !== selectedStatus) {
                row.style.display = 'none';
                return;
            }

            // Hospital filter / zipcode matching
            let matchesHospital = true;
            if (hospitalFilter) matchesHospital = hospitalText.indexOf(hospitalFilter) > -1;
            let matchesZip = false;
            if (hospitalsFromZip && hospitalsFromZip.length) matchesZip = hospitalsFromZip.some(name => hospitalText.indexOf(name) > -1);

            if ((hospitalFilter === '' || matchesHospital || matchesZip)) {
                row.style.display = '';
                if (rowStatus) groupHasVisibleRows[rowStatus] = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Second: show/hide group headers. If a specific status is selected, show only that group's header (if it has rows)
        rows.forEach(row => {
            if (!row.classList.contains('group-header')) return;
            const groupStatus = row.getAttribute('data-group-status');
            if (selectedStatus) {
                // show only the selected group's header when selected
                row.style.display = (groupStatus === selectedStatus && groupHasVisibleRows[groupStatus]) ? '' : 'none';
            } else {
                // no status filter selected: show header only if group has visible rows
                row.style.display = groupHasVisibleRows[groupStatus] ? '' : 'none';
            }
        });
    }

    searchInput.addEventListener('keyup', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
});
</script>
</body>
</html>
