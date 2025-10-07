<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกงานประจำวัน | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php include './components/navbar.php'; ?>
    <div id="dailyPage">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">บันทึกงานประจำวัน</h2>

                <form class="space-y-6" method="POST" action="#">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">วันที่ <span class="text-red-500">*</span></label>
                            <input type="date" name="work_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาเริ่มงาน</label>
                            <input type="time" name="start_time" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">งานที่ทำ <span class="text-red-500">*</span></label>
                        <textarea name="work_description" rows="4" placeholder="อธิบายงานที่ทำในวันนี้..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required></textarea>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">สถานะงาน</label>
                            <select name="work_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="completed">เสร็จสมบูรณ์</option>
                                <option value="in_progress">กำลังดำเนินการ</option>
                                <option value="pending">รอดำเนินการ</option>
                                <option value="on_hold">หยุดชั่วคราว</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาสิ้นสุดงาน</label>
                            <input type="time" name="end_time" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                        <textarea name="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <button type="reset" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">ล้างข้อมูล</button>
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-blue-700 transition-colors">บันทึกงาน</button>
                    </div>
                </form>
            </div>

            <!-- Work History -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">ประวัติการทำงาน</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">วันที่</th>
                                <th class="px-6 py-3">เวลา</th>
                                <th class="px-6 py-3">งานที่ทำ</th>
                                <th class="px-6 py-3">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">2024-01-15</td>
                                <td class="px-6 py-4">09:00 - 17:00</td>
                                <td class="px-6 py-4">ซ่อมคอมพิวเตอร์แผนกบัญชี</td>
                                <td class="px-6 py-4"><span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">เสร็จสมบูรณ์</span></td>
                            </tr>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">2024-01-14</td>
                                <td class="px-6 py-4">10:30 - 15:30</td>
                                <td class="px-6 py-4">ติดตั้งโปรแกรมใหม่</td>
                                <td class="px-6 py-4"><span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">กำลังดำเนินการ</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>