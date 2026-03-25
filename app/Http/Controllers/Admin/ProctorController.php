<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\ExamProctor;
use App\Models\ProctorAssignment;
use Carbon\Carbon;

class ProctorController extends Controller
{
    public function index()
    {
        $exams = Exam::orderBy('created_at', 'desc')->get();
        return view('admin.proctors.index', compact('exams'));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'exam_name' => 'required|string',
            'start_date' => 'required|date',
            'total_days' => 'required|integer|min:1',
            'rooms_per_day' => 'required|integer|min:1',
            'import_data' => 'required|string' 
        ]);

        $proctorsList = json_decode($request->import_data, true);
        if (empty($proctorsList)) {
            return back()->with('error', 'Vui lòng import danh sách giám thị trước khi phân công!');
        }

        // Nhận diện các ràng buộc được BẬT từ giao diện
        $constraintDept = $request->has('constraint_dept');
        $constraintRoom = $request->has('constraint_room');
        $constraintPair = $request->has('constraint_pair');

        $exam = Exam::create([
            'name' => $request->exam_name,
            'start_date' => $request->start_date,
            'total_days' => $request->total_days,
            'rooms_per_day' => $request->rooms_per_day,
        ]);

        $examProctors = [];
        foreach ($proctorsList as $p) {
            $examProctors[] = ExamProctor::create([
                'exam_id' => $exam->id,
                'proctor_name' => $p['name'],
                'proctor_code' => $p['code'] ?? null,
                'department' => $p['department'] ?? 'Chưa cập nhật'
            ]);
        }

        $startDate = Carbon::parse($exam->start_date);
        $proctorRoomsHistory = []; 
        $proctorPairsHistory = []; 

        for ($day = 0; $day < $exam->total_days; $day++) {
            $currentDate = $startDate->copy()->addDays($day);
            // MẢNG QUAN TRỌNG: Lưu những ai đã gác trong ngày hôm nay
            $assignedToday = []; 

            for ($room = 1; $room <= $exam->rooms_per_day; $room++) {
                $roomName = 'Phòng ' . str_pad($room, 2, '0', STR_PAD_LEFT);
                $roomProctors = []; 

                foreach (['GT1', 'GT2'] as $role) {
                    $availableProctors = collect($examProctors)->shuffle();
                    $selectedProctor = null;

                    foreach ($availableProctors as $proctor) {
                        // LUẬT THÉP: 1 Người không bao giờ gác 2 phòng trong 1 ngày
                        if (in_array($proctor->id, $assignedToday)) continue;

                        // Áp dụng ràng buộc nếu được BẬT
                        if ($constraintDept) {
                            $sameDept = false;
                            foreach ($roomProctors as $rp) {
                                if (!empty($proctor->department) && !empty($rp->department) && $proctor->department === $rp->department) {
                                    $sameDept = true; break;
                                }
                            }
                            if ($sameDept) continue;
                        }

                        if ($constraintRoom) {
                            if (isset($proctorRoomsHistory[$proctor->id]) && in_array($roomName, $proctorRoomsHistory[$proctor->id])) {
                                continue;
                            }
                        }

                        if ($constraintPair) {
                            $pairedBefore = false;
                            if (isset($proctorPairsHistory[$proctor->id])) {
                                foreach ($roomProctors as $rp) {
                                    if (in_array($rp->id, $proctorPairsHistory[$proctor->id])) {
                                        $pairedBefore = true; break;
                                    }
                                }
                            }
                            if ($pairedBefore) continue;
                        }

                        // Nếu qua hết các bài test -> Chọn người này
                        $selectedProctor = $proctor;
                        break;
                    }

                    // Fallback: Nếu quá thiếu giám thị, chỉ cần đảm bảo người đó CHƯA GÁC HÔM NAY là được xếp vào cho đủ phòng
                    if (!$selectedProctor) {
                        foreach ($availableProctors as $proctor) {
                            if (!in_array($proctor->id, $assignedToday)) {
                                $selectedProctor = $proctor; break;
                            }
                        }
                    }

                    if ($selectedProctor) {
                        ProctorAssignment::create([
                            'exam_id' => $exam->id,
                            'exam_proctor_id' => $selectedProctor->id,
                            'assign_date' => $currentDate->format('Y-m-d'),
                            'role' => $role,
                            'room_name' => $roomName
                        ]);

                        // Đánh dấu đã gác hôm nay
                        $assignedToday[] = $selectedProctor->id;
                        
                        // Cập nhật lịch sử để so sánh cho các ngày sau
                        foreach ($roomProctors as $rp) {
                            $proctorPairsHistory[$selectedProctor->id][] = $rp->id;
                            $proctorPairsHistory[$rp->id][] = $selectedProctor->id;
                        }
                        $roomProctors[] = $selectedProctor;
                        $proctorRoomsHistory[$selectedProctor->id][] = $roomName;
                    }
                }
            }
        }

        return redirect()->route('admin.proctors.index', ['auto_load_exam' => $exam->id])
                         ->with('success', 'Đã phân công thành công kỳ thi: ' . $exam->name);
    }

    public function history(Request $request)
    {
        $exam = Exam::find($request->exam_id);
        if (!$exam) return response()->json(['error' => 'Not found']);

        $proctors = ExamProctor::where('exam_id', $exam->id)->orderBy('proctor_name')->get();
        $assignments = ProctorAssignment::where('exam_id', $exam->id)->get();
        
        // Lấy danh sách các ngày thi
        $dates = $assignments->pluck('assign_date')->unique()->sort()->values();
        $historyByDate = [];

        foreach ($dates as $date) {
            $formattedDate = Carbon::parse($date)->format('d/m/Y');
            $dailyData = [];
            $dailyAssignments = $assignments->where('assign_date', $date);

            // Giữ nguyên danh sách Giám thị, chỉ thay đổi dữ liệu phòng thi bên trong
            foreach ($proctors as $p) {
                $proctorAssigns = $dailyAssignments->where('exam_proctor_id', $p->id);
                $dailyData[] = [
                    'name' => $p->proctor_name,
                    'department' => $p->department,
                    'gt1' => $proctorAssigns->where('role', 'GT1')->count() > 0 ? true : false,
                    'gt2' => $proctorAssigns->where('role', 'GT2')->count() > 0 ? true : false,
                    'room' => $proctorAssigns->pluck('room_name')->first() ?: '(Trống)' 
                ];
            }
            $historyByDate[$formattedDate] = $dailyData;
        }

        return response()->json([
            'exam_name' => $exam->name,
            'dates' => array_keys($historyByDate),
            'data_by_date' => $historyByDate       
        ]);
    }
}