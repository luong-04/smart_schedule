<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for clean wipe
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\Assignment::truncate();
        \App\Models\Schedule::truncate();
        \App\Models\Teacher::truncate();
        \App\Models\Subject::truncate();
        \App\Models\Classroom::truncate();
        \App\Models\Room::truncate();
        \App\Models\RoomType::truncate();
        \App\Models\SubjectConfiguration::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 1. Room Types
        $rtTheory = \App\Models\RoomType::create(['name' => 'Phòng học lý thuyết']);
        $rtIT = \App\Models\RoomType::create(['name' => 'Phòng Tin học']);
        $rtLab = \App\Models\RoomType::create(['name' => 'Phòng Thí nghiệm']);
        $rtGym = \App\Models\RoomType::create(['name' => 'Sân vận động / Bãi tập']);

        // 2. Subjects (MOET 2018 Curriculum)
        $subjectsData = [
            // Compulsory
            ['name' => 'Toán học', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'Ngữ văn', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'Tiếng Anh', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'Lịch sử', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'Giáo dục thể chất', 'type' => 'practice', 'room_type_id' => $rtGym->id],
            ['name' => 'Giáo dục quốc phòng và an ninh', 'type' => 'practice', 'room_type_id' => $rtGym->id],
            ['name' => 'Hoạt động trải nghiệm, hướng nghiệp', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'Nội dung giáo dục địa phương', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            // Elective
            ['name' => 'Vật lí', 'type' => 'practice', 'room_type_id' => $rtLab->id],
            ['name' => 'Hóa học', 'type' => 'practice', 'room_type_id' => $rtLab->id],
            ['name' => 'Sinh học', 'type' => 'practice', 'room_type_id' => $rtLab->id],
            ['name' => 'Địa lí', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'GD Kinh tế và Pháp luật', 'type' => 'theory', 'room_type_id' => $rtTheory->id],
            ['name' => 'Tin học', 'type' => 'practice', 'room_type_id' => $rtIT->id],
            ['name' => 'Công nghệ', 'type' => 'practice', 'room_type_id' => $rtTheory->id],
        ];

        $subjects = [];
        foreach ($subjectsData as $sData) {
            $subjects[$sData['name']] = \App\Models\Subject::create($sData);
        }

        // 3. Rooms
        for ($i = 1; $i <= 20; $i++) {
            \App\Models\Room::create(['name' => 'Phòng ' . (100 + $i), 'room_type_id' => $rtTheory->id]);
        }
        \App\Models\Room::create(['name' => 'Lab Lý - Sinh', 'room_type_id' => $rtLab->id]);
        \App\Models\Room::create(['name' => 'Lab Hóa', 'room_type_id' => $rtLab->id]);
        \App\Models\Room::create(['name' => 'Phòng Tin 1', 'room_type_id' => $rtIT->id]);
        \App\Models\Room::create(['name' => 'Phòng Tin 2', 'room_type_id' => $rtIT->id]);
        \App\Models\Room::create(['name' => 'Sân bóng đa năng', 'room_type_id' => $rtGym->id]);

        // 4. Teachers (Increased pool to 60 to reduce scheduling conflicts)
        $teacherNames = [
            'Nguyễn Văn An',
            'Trần Thị Bình',
            'Lê Văn Cường',
            'Phạm Thị Dung',
            'Hoàng Văn Em',
            'Vũ Thị Phương',
            'Đỗ Văn Giang',
            'Bùi Thị Hoa',
            'Phan Văn Hùng',
            'Ngô Thị Lan',
            'Lý Văn Minh',
            'Đặng Thị Ngọc',
            'Mai Văn Nam',
            'Trịnh Thị Oanh',
            'Đoàn Văn Phúc',
            'Quách Thị Quỳnh',
            'Sơn Văn Sang',
            'Thái Thị Thảo',
            'Uông Văn Uy',
            'Vi Thị Vân',
            'Lương Văn Xứng',
            'Triệu Thị Yên',
            'Hà Văn Zinh',
            'Tạ Văn Hải',
            'Cao Thị Liên',
            'Dương Văn Quân',
            'Chu Thị Mai',
            'Nông Văn Thắng',
            'Lâm Thị Tuyết',
            'Âu Văn Vương',
            'Phùng Văn Đạt',
            'Mạc Thị Cúc',
            'Tô Văn Bính',
            'Hồ Thị Đào',
            'Bạch Văn Định',
            'Trần Văn Hưng',
            'Lê Thị Mỹ',
            'Phạm Văn Thành',
            'Hoàng Thị Yến',
            'Vũ Văn Khải',
            'Đỗ Thị Thư',
            'Bùi Văn Tâm',
            'Phan Thị Ngọc',
            'Ngô Văn Lộc',
            'Lý Thị Hà',
            'Đặng Văn Khoa',
            'Mai Thị Diệp',
            'Trịnh Văn Quý',
            'Đoàn Thị Trang',
            'Quách Văn Bảo',
            'Thái Văn Chương',
            'Vi Thị Hồng',
            'Dương Văn Tiến',
            'Chu Thị Phượng',
            'Lâm Văn Sỹ',
            'Âu Thị Nga',
            'Phùng Văn Khang',
            'Mạc Thị Lan',
            'Tô Văn Toàn',
            'Hồ Thị Bích'
        ];

        $teachers = [];
        foreach ($teacherNames as $index => $name) {
            $teachers[] = \App\Models\Teacher::create([
                'name' => $name,
                'code' => 'GV' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'department' => 'Tổ ' . ($index % 5 + 1),
                'max_slots_week' => 24,
                'off_days' => []
            ]);
        }

        // 5. Classrooms (15 classes, distributed across blocks)
        $grades = [10, 11, 12];
        $blocks = ['KHTN', 'KHXH', 'Cơ bản'];
        $classrooms = [];

        foreach ($grades as $grade) {
            foreach (range(1, 5) as $num) {
                // Distribute: 10A1, 10A2 (KHTN), 10A3, 10A4 (KHXH), 10A5 (Cơ bản)
                if ($num <= 2)
                    $block = 'KHTN';
                elseif ($num <= 4)
                    $block = 'KHXH';
                else
                    $block = 'Cơ bản';

                $shift = ($grade == 12 || ($grade == 11 && $num <= 2)) ? 'morning' : 'afternoon';

                $classrooms[] = \App\Models\Classroom::create([
                    'name' => $grade . 'A' . $num,
                    'grade' => $grade,
                    'block' => $block,
                    'shift' => $shift,
                    'homeroom_teacher_id' => $teachers[array_rand($teachers)]->id
                ]);
            }
        }

        // 6. Subject Configurations (Weekly periods)
        // Group 1: Compulsory for everyone
        $compulsory = [
            'Toán học' => 3,
            'Ngữ văn' => 3,
            'Tiếng Anh' => 3,
            'Lịch sử' => 2,
            'Giáo dục thể chất' => 2,
            'Giáo dục quốc phòng và an ninh' => 1,
            'Hoạt động trải nghiệm, hướng nghiệp' => 3,
            'Nội dung giáo dục địa phương' => 1
        ];

        // Group 2: Block-specific Electives
        $electives = [
            'KHTN' => ['Vật lí' => 2, 'Hóa học' => 2, 'Sinh học' => 2, 'Tin học' => 2],
            'KHXH' => ['Địa lí' => 2, 'GD Kinh tế và Pháp luật' => 2, 'Công nghệ' => 2, 'Tin học' => 2],
            'Cơ bản' => ['Vật lí' => 2, 'Địa lí' => 2, 'Công nghệ' => 2, 'Tin học' => 2],
        ];

        foreach ($grades as $grade) {
            foreach ($blocks as $block) {
                // Add Compulsory
                foreach ($compulsory as $sName => $slots) {
                    \App\Models\SubjectConfiguration::create([
                        'subject_id' => $subjects[$sName]->id,
                        'grade' => $grade,
                        'block' => $block,
                        'slots_per_week' => $slots
                    ]);
                }
                // Add Electives
                foreach ($electives[$block] as $sName => $slots) {
                    \App\Models\SubjectConfiguration::create([
                        'subject_id' => $subjects[$sName]->id,
                        'grade' => $grade,
                        'block' => $block,
                        'slots_per_week' => $slots
                    ]);
                }
            }
        }

        // 7. Assignments
        $subjectTeachers = [];
        $tIdx = 0;
        foreach ($subjects as $name => $sub) {
            // Assign 2-3 teachers per subject
            for ($i = 0; $i < 3; $i++) {
                $subjectTeachers[$sub->id][] = $teachers[$tIdx % count($teachers)]->id;
                $tIdx++;
            }
        }

        foreach ($classrooms as $cls) {
            // Get subjects configured for this class's grade and block
            $configSubjectIds = \App\Models\SubjectConfiguration::where('grade', $cls->grade)
                ->where('block', $cls->block)
                ->pluck('subject_id')
                ->toArray();

            foreach ($configSubjectIds as $sId) {
                $possibleTeachers = $subjectTeachers[$sId];
                \App\Models\Assignment::create([
                    'class_id' => $cls->id,
                    'subject_id' => $sId,
                    'teacher_id' => $possibleTeachers[array_rand($possibleTeachers)]
                ]);
            }
        }
    }
}
