<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\SubjectConfiguration;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class ProductionDataSeeder extends Seeder
{
    private $teacherLoad = []; // Track slots per teacher

    public function run()
    {
        // 1. DỌN DẸP DỮ LIỆU CŨ
        $this->cleanup();

        // 1b. KHỞI TẠO CÀI ĐẶT
        $this->seedSettings();

        // 2. TẠO LOẠI PHÒNG & PHÒNG HỌC
        $rtTheory = RoomType::firstOrCreate(['name' => 'Phòng học lý thuyết']);
        $rtLab = RoomType::firstOrCreate(['name' => 'Phòng thí nghiệm Lý-Hóa-Sinh']);
        $rtIT = RoomType::firstOrCreate(['name' => 'Phòng máy tính']);
        $rtTech = RoomType::firstOrCreate(['name' => 'Phòng thực hành Công nghệ']);
        $rtGym = RoomType::firstOrCreate(['name' => 'Nhà đa năng / Sân bãi']);

        // Tạo 24 phòng học lý thuyết cho 24 lớp
        for ($i = 1; $i <= 24; $i++) {
            Room::create(['name' => 'P.' . str_pad($i, 3, '0', STR_PAD_LEFT), 'room_type_id' => $rtTheory->id]);
        }
        Room::create(['name' => 'Lab 01', 'room_type_id' => $rtLab->id]);
        Room::create(['name' => 'Lab 02', 'room_type_id' => $rtLab->id]);
        Room::create(['name' => 'Tin 01', 'room_type_id' => $rtIT->id]);
        Room::create(['name' => 'Tin 02', 'room_type_id' => $rtIT->id]);
        Room::create(['name' => 'Kỹ thuật 01', 'room_type_id' => $rtTech->id]);
        Room::create(['name' => 'Sân vận động', 'room_type_id' => $rtGym->id]);

        // 3. TẠO MÔN HỌC (Chuẩn MOET 2018) - Tách biệt từng Tổ
        $subjects = [
            ['name' => 'Toán học', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Toán'],
            ['name' => 'Ngữ văn', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Ngữ Văn'],
            ['name' => 'Tiếng Anh', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Ngoại Ngữ'],
            ['name' => 'Lịch sử', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Lịch Sử'],
            ['name' => 'Địa lí', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Địa Lý'],
            ['name' => 'Vật lí', 'type' => 'theory', 'room_type_id' => $rtLab->id, 'dept' => 'Tổ Vật Lý'],
            ['name' => 'Hóa học', 'type' => 'theory', 'room_type_id' => $rtLab->id, 'dept' => 'Tổ Hóa Học'],
            ['name' => 'Sinh học', 'type' => 'theory', 'room_type_id' => $rtLab->id, 'dept' => 'Tổ Sinh Học'],
            ['name' => 'GD Kinh tế và Pháp luật', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ GDKT&PL'],
            ['name' => 'Tin học', 'type' => 'practice', 'room_type_id' => $rtIT->id, 'dept' => 'Tổ Tin Học'],
            ['name' => 'Công nghệ', 'type' => 'practice', 'room_type_id' => $rtTech->id, 'dept' => 'Tổ Công Nghệ'],
            ['name' => 'Giáo dục thể chất', 'type' => 'practice', 'room_type_id' => $rtGym->id, 'dept' => 'Tổ GDTC'],
            ['name' => 'Giáo dục quốc phòng và an ninh', 'type' => 'practice', 'room_type_id' => $rtGym->id, 'dept' => 'Tổ GDQP'],
            ['name' => 'Hoạt động trải nghiệm, hướng nghiệp', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Năng Khiếu'],
            ['name' => 'Nội dung giáo dục địa phương', 'type' => 'theory', 'room_type_id' => null, 'dept' => 'Tổ Sử-Địa'],
        ];

        $subjectModels = [];
        foreach ($subjects as $s) {
            $subjectModels[$s['name']] = Subject::create([
                'name' => $s['name'], 'type' => $s['type'], 'room_type_id' => $s['room_type_id']
            ]);
        }

        // 4. DANH SÁCH TÊN GIÁO VIÊN VIỆT NAM
        $vnNames = [
            'Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Cường', 'Phạm Minh Đức', 'Vũ Thu Thảo',
            'Đặng Quang Hải', 'Bùi Thị Lan', 'Đỗ Mạnh Hùng', 'Ngô Thanh Hà', 'Hoàng Văn Nam',
            'Lý Minh Tuấn', 'Đoàn Thị Mai', 'Phan Văn Phú', 'Trịnh Kim Oanh', 'Tạ Quang Thắng',
            'Lương Minh Hạnh', 'Chu Thế Vinh', 'Phùng Thu Trang', 'Hà Văn Lợi', 'Mạc Đăng Khoa',
            'Nguyễn Thị Kim Chi', 'Trần Bảo Long', 'Lê Tuấn Kiệt', 'Phạm Huỳnh Nam', 'Vũ Ngọc Diệp',
            'Đặng Xuân Bắc', 'Bùi Minh Trí', 'Đỗ Phương Ly', 'Ngô Gia Bảo', 'Hoàng Bảo Ngọc',
            'Nguyễn Minh Quang', 'Trần Thị Tuyết', 'Lê Hồng Anh', 'Phạm Quốc Huy', 'Vũ Đình Trọng',
            'Đặng Việt Dũng', 'Bùi Xuân Hinh', 'Đỗ Thùy Linh', 'Ngô Công Khanh', 'Hoàng Yến Nhi',
            'Nguyễn Hữu Đa', 'Trần Khả Ái', 'Lê Hữu Nam', 'Phạm Ngọc Trinh', 'Vũ Minh Khang',
            'Đặng Thu Hà', 'Bùi Văn Quyết', 'Đỗ Hữu Thắng', 'Ngô Phương Thúy', 'Hoàng Quốc Việt',
            'Nguyễn Thành Trung', 'Trần Văn Sang', 'Lê Minh Thành', 'Phạm Thị Thùy', 'Vũ Quốc Anh',
            'Nguyễn Diệu Linh', 'Trần Minh Quân', 'Lê Quang dũng', 'Phạm Thu Thủy', 'Vũ Đức Duy',
            'Sơn Tùng MTP', 'Đen Vâu', 'Hoàng Thùy Linh', 'Binz Đan Lê', 'Suboi Hàng',
            'Trấn Thành Ngô', 'Ninh Dương Lan Ngọc', 'Trường Giang Võ', 'Việt Hương Chu',
            'Hồ Hoài Anh', 'Lưu Hương Giang', 'Đông Nhi Mai', 'Ông Cao Thắng', 'Noo Phước Thịnh',
            'Tóc Tiên', 'Soobin Hoàng Sơn', 'Trung Quân Idol', 'Bảo Anh', 'Hương Tràm',
        ];
        shuffle($vnNames);

        // Tạo 60 Giáo viên (Phân bổ theo tổ)
        $teacherPool = [];
        $depts = collect($subjects)->pluck('dept')->unique();
        foreach ($depts as $d) $teacherPool[$d] = []; // Initialize keys

        $nameIndex = 0;
        foreach ($depts as $dept) {
            // Mỗi tổ ít nhất 4-6 giáo viên để gánh tải
            $count = ($dept === 'Tổ Toán' || $dept === 'Tổ Ngữ Văn' || $dept === 'Tổ Ngoại Ngữ') ? 8 : 4;
            for ($i=0; $i<$count; $i++) {
                if ($nameIndex >= count($vnNames)) break;
                $t = Teacher::create([
                    'name' => $vnNames[$nameIndex++],
                    'code' => 'GV' . str_pad($nameIndex, 3, '0', STR_PAD_LEFT),
                    'department' => $dept,
                    'max_slots_week' => 17
                ]);
                $teacherPool[$dept][] = $t;
            }
        }

        // 5. TẠO 24 LỚP HỌC
        $classrooms = [];
        $grades = [10, 11, 12];
        foreach ($grades as $grade) {
            for ($i = 1; $i <= 8; $i++) {
                $block = ($i <= 3) ? 'KHTN' : (($i <= 6) ? 'KHXH' : 'Cơ bản');
                $shift = (rand(0, 1) == 0) ? 'morning' : 'afternoon';
                $classrooms[] = Classroom::create([
                    'name' => $grade . 'A' . $i, 'grade' => $grade, 'block' => $block, 'shift' => $shift
                ]);
            }
        }

        // Gán GVCN
        $allTeachers = Teacher::all();
        foreach ($classrooms as $index => $cls) {
            $cls->homeroom_teacher_id = $allTeachers[$index]->id; $cls->save();
        }

        // 6. CẤU HÌNH & PHÂN CÔNG (Strict 17 slots)
        $this->seedCurriculum($subjectModels);
        $this->seedAssignmentsBalanced($classrooms, $subjects, $teacherPool);

        // 7. XẾP TKB
        $this->seedSchedules($classrooms);
    }

    private function seedAssignmentsBalanced($classrooms, $subjectData, $teacherPool)
    {
        $this->teacherLoad = [];
        foreach (Teacher::all() as $t) $this->teacherLoad[$t->id] = 0;

        foreach ($classrooms as $cls) {
            $configs = SubjectConfiguration::where('grade', $cls->grade)->where('block', $cls->block)->get();
            foreach ($configs as $cfg) {
                $slots = $cfg->slots_per_week;
                $subjectName = $cfg->subject->name;
                $deptName = collect($subjectData)->where('name', $subjectName)->first()['dept'];
                
                // Tìm giáo viên trong tổ có load thấp nhất và còn slot
                $availableTeachers = collect($teacherPool[$deptName])->filter(function($t) use ($slots) {
                    return ($this->teacherLoad[$t->id] + $slots) <= 17;
                })->sortBy(function($t) {
                    return $this->teacherLoad[$t->id];
                });

                if ($availableTeachers->isEmpty()) {
                    // Fallback: Tìm bất kỳ ai cùng tổ (nếu quá tải toàn bộ thì buộc phải vượt nhẹ hoặc lấy người rảnh nhất)
                    $t = collect($teacherPool[$deptName])->sortBy(fn($x) => $this->teacherLoad[$x->id])->first();
                } else {
                    $t = $availableTeachers->first();
                }

                Assignment::create(['class_id' => $cls->id, 'teacher_id' => $t->id, 'subject_id' => $cfg->subject_id]);
                $this->teacherLoad[$t->id] += $slots;
            }
        }
    }

    private function cleanup()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schedule::truncate(); Assignment::truncate(); SubjectConfiguration::truncate();
        Classroom::truncate(); Teacher::truncate(); Subject::truncate(); Room::truncate(); RoomType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedSettings()
    {
        $defaults = [
            'school_name' => 'TRƯỜNG THPT SMART-SCHEDULE', 'school_year' => '2024-2025', 'semester' => 'Học kỳ 1',
            'morning_flag_day' => 2, 'morning_flag_period' => 1, 'morning_meeting_day' => 7, 'morning_meeting_period' => 5,
            'afternoon_flag_day' => 2, 'afternoon_flag_period' => 6, 'afternoon_meeting_day' => 7, 'afternoon_meeting_period' => 10,
            'active_schedule' => 'Học kỳ 1 - 2024-2025'
        ];
        foreach ($defaults as $k => $v) Setting::updateOrCreate(['key' => $k], ['value' => $v]);
    }

    private function seedCurriculum($sm)
    {
        $grades = [10, 11, 12]; $blocks = ['KHTN', 'KHXH', 'Cơ bản'];
        foreach ($grades as $grade) {
            foreach ($blocks as $block) {
                SubjectConfiguration::create(['subject_id'=>$sm['Toán học']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>4]);
                SubjectConfiguration::create(['subject_id'=>$sm['Ngữ văn']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>4]);
                SubjectConfiguration::create(['subject_id'=>$sm['Tiếng Anh']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>3]);
                SubjectConfiguration::create(['subject_id'=>$sm['Lịch sử']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                SubjectConfiguration::create(['subject_id'=>$sm['Giáo dục thể chất']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                SubjectConfiguration::create(['subject_id'=>$sm['Giáo dục quốc phòng và an ninh']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>1]);
                SubjectConfiguration::create(['subject_id'=>$sm['Hoạt động trải nghiệm, hướng nghiệp']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>1]);
                SubjectConfiguration::create(['subject_id'=>$sm['Nội dung giáo dục địa phương']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>1]);
                if ($block == 'KHTN') {
                    SubjectConfiguration::create(['subject_id'=>$sm['Vật lí']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>3]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Hóa học']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>3]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Sinh học']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>3]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Tin học']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                } elseif ($block == 'KHXH') {
                    SubjectConfiguration::create(['subject_id'=>$sm['Địa lí']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>3]);
                    SubjectConfiguration::create(['subject_id'=>$sm['GD Kinh tế và Pháp luật']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>3]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Vật lí']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Công nghệ']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                } else {
                    SubjectConfiguration::create(['subject_id'=>$sm['Vật lí']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Địa lí']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Tin học']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                    SubjectConfiguration::create(['subject_id'=>$sm['Công nghệ']->id, 'grade'=>$grade, 'block'=>$block, 'slots_per_week'=>2]);
                }
            }
        }
    }

    private function seedSchedules($classrooms)
    {
        $name = 'Học kỳ 1 - 2024-2025';
        $specialRooms = Room::whereDoesntHave('roomType', fn($q) => $q->where('name', 'Phòng học lý thuyết'))->get();
        $settings = ['morning' => ['range'=>range(1,5), 'fixed'=>['2-1','7-5']], 'afternoon' => ['range'=>range(6,10), 'fixed'=>['2-6','7-10']]];

        $versions = [
            ['from' => '2026-04-13', 'to' => '2026-04-26', 'label' => ' (v1)'],
            ['from' => '2026-04-27', 'to' => '2026-12-31', 'label' => ' (v2)']
        ];

        foreach ($versions as $v) {
            $tSlots = []; // Reset teacher availability for each version
            $rSlots = []; // Reset room availability
            
            foreach ($classrooms as $cls) {
                $assignments = Assignment::with('subject')->where('class_id', $cls->id)->get();
                $shift = $cls->shift; 
                $s = $settings[$shift]; 
                $cSlots = [];
                $oppShift = ($shift === 'morning') ? 'afternoon' : 'morning';
                $os = $settings[$oppShift];

                foreach ($assignments as $as) {
                    $count = SubjectConfiguration::where('grade', $cls->grade)->where('block', $cls->block)->where('subject_id', $as->subject_id)->first()->slots_per_week ?? 0;
                    $isOpp = in_array($as->subject->name, ['Giáo dục thể chất', 'Giáo dục quốc phòng và an ninh']);
                    $targetRange = $isOpp ? $os['range'] : $s['range'];
                    $targetFixed = $isOpp ? $os['fixed'] : $s['fixed'];

                    $placed = 0; $possible = [];
                    foreach ([2,3,4,5,6,7] as $d) { foreach ($targetRange as $p) { $possible[] = ['d'=>$d, 'p'=>$p]; } }
                    shuffle($possible); // Different shuffle each time ensures v1 and v2 are different

                    while ($placed < $count && !empty($possible)) {
                        $slot = array_shift($possible); $key = "{$slot['d']}-{$slot['p']}";
                        if (in_array($key, $cSlots) || in_array($key, $targetFixed)) continue;
                        if (isset($tSlots[$as->teacher_id]) && in_array($key, $tSlots[$as->teacher_id])) continue;

                        $roomID = null;
                        if ($as->subject->type === 'practice') {
                            foreach ($specialRooms as $r) {
                                if ($r->room_type_id == $as->subject->room_type_id && (!isset($rSlots[$r->id]) || !in_array($key, $rSlots[$r->id]))) {
                                    $roomID = $r->id; break;
                                }
                            }
                            if (!$roomID) continue;
                        }

                        Schedule::create([
                            'schedule_name' => $name, 
                            'applies_from' => $v['from'], 
                            'applies_to' => $v['to'], 
                            'assignment_id' => $as->id, 
                            'class_id' => $as->class_id, 
                            'teacher_id' => $as->teacher_id, 
                            'room_id' => $roomID, 
                            'day_of_week' => $slot['d'], 
                            'period' => $slot['p']
                        ]);
                        $tSlots[$as->teacher_id][] = $key;
                        if ($roomID) $rSlots[$roomID][] = $key;
                        $cSlots[] = $key; 
                        $placed++;
                    }
                }
            }
        }
    }
}
