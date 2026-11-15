<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$questions = [
    // Tier 1: Culture & Everyday Korean
    [
        'id' => 'c1',
        'category' => 'culture',
        'category_title' => 'Văn hóa & Phim ảnh',
        'question' => 'Câu nói phổ biến nhất trong phim Hàn "안녕하세요" (An-nyeong-ha-se-yo) có nghĩa là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Cảm ơn bạn', 'correct' => false],
            ['key' => 'B', 'text' => 'Xin chào', 'correct' => true],
            ['key' => 'C', 'text' => 'Tạm biệt', 'correct' => false]
        ],
        'feedback' => 'Chính xác! "안녕하세요" là lời chào lịch sự chuẩn mực nhất của người Hàn Quốc, dùng chào hỏi hàng ngày.'
    ],
    [
        'id' => 'c2',
        'category' => 'culture',
        'category_title' => 'Văn hóa & Phim ảnh',
        'question' => 'Khi muốn nói lời cảm ơn lịch sự và trang trọng nhất trong tiếng Hàn, người ta dùng câu nào?',
        'options' => [
            ['key' => 'A', 'text' => '감사합니다 (Gam-sa-ham-ni-da)', 'correct' => true],
            ['key' => 'B', 'text' => '미안해요 (Mi-an-hae-yo)', 'correct' => false],
            ['key' => 'C', 'text' => '사랑해요 (Sa-rang-hae-yo)', 'correct' => false]
        ],
        'feedback' => 'Rất chuẩn! "감사합니다" là cách cảm ơn trang trọng nhất, dùng trong mọi tình huống giao tiếp và công sở.'
    ],
    [
        'id' => 'c3',
        'category' => 'culture',
        'category_title' => 'Văn hóa & Phim ảnh',
        'question' => 'Trong văn hóa Hàn Quốc, một bạn nữ gọi người anh trai hoặc bạn nam thân thiết lớn tuổi hơn là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Hyung (형)', 'correct' => false],
            ['key' => 'B', 'text' => 'Oppa (오빠)', 'correct' => true],
            ['key' => 'C', 'text' => 'Noona (누나)', 'correct' => false]
        ],
        'feedback' => 'Đúng rồi! Nữ gọi anh lớn tuổi là "Oppa" (오빠), còn nam gọi anh lớn tuổi là "Hyung" (형).'
    ],
    [
        'id' => 'c4',
        'category' => 'culture',
        'category_title' => 'Văn hóa & Phim ảnh',
        'question' => 'Món ăn truyền thống lên men cay nồng mang tính biểu tượng văn hóa của Hàn Quốc là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Kimchi (김치)', 'correct' => true],
            ['key' => 'B', 'text' => 'Tokbokki (떡볶이)', 'correct' => false],
            ['key' => 'C', 'text' => 'Kimbap (김밥)', 'correct' => false]
        ],
        'feedback' => 'Tuyệt vời! Kimchi là linh hồn của ẩm thực Hàn Quốc, xuất hiện trong mọi bữa ăn gia đình.'
    ],

    // Tier 2: Hangul & Linguistics
    [
        'id' => 'l1',
        'category' => 'language',
        'category_title' => 'Chữ cái & Ngữ âm Hangul',
        'question' => 'Bảng chữ cái tiếng Hàn (Hangul) được Vua Sejong sáng tạo dựa trên 3 yếu tố triết lý phương Đông nào?',
        'options' => [
            ['key' => 'A', 'text' => 'Trời (•) – Đất (ㅡ) – Người (ㅣ)', 'correct' => true],
            ['key' => 'B', 'text' => 'Mặt Trời – Mặt Trăng – Vì Sao', 'correct' => false],
            ['key' => 'C', 'text' => 'Kim – Mộc – Thủy – Hỏa – Thổ', 'correct' => false]
        ],
        'feedback' => 'Xuất sắc! Vua Sejong sáng tạo Hangul theo 3 nét cơ bản đại diện cho Trời tròn, Đất phẳng và Con người đứng thẳng.'
    ],
    [
        'id' => 'l2',
        'category' => 'language',
        'category_title' => 'Chữ cái & Ngữ âm Hangul',
        'question' => 'Bảng chữ cái Hangul được UNESCO công nhận di sản vì đặc điểm khoa học độc đáo nào?',
        'options' => [
            ['key' => 'A', 'text' => 'Hình dạng phụ âm mô phỏng khẩu hình miệng và vị trí lưỡi khi phát âm', 'correct' => true],
            ['key' => 'B', 'text' => 'Là dạng chữ tượng hình khắc trên mai rùa cổ', 'correct' => false],
            ['key' => 'C', 'text' => 'Có 6 thanh điệu giống hệt tiếng Việt', 'correct' => false]
        ],
        'feedback' => 'Chính xác! Các phụ âm Hangul mô phỏng đúng khẩu hình miệng, giúp người mới học nhớ mặt chữ chỉ sau 60 phút!'
    ],
    [
        'id' => 'l3',
        'category' => 'language',
        'category_title' => 'Chữ cái & Ngữ âm Hangul',
        'question' => 'Khoảng 60% từ vựng tiếng Hàn là gốc Hán - Hàn (rất gần gũi với âm Hán - Việt). Từ "학생" (Hak-saeng) có nghĩa là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Học sinh', 'correct' => true],
            ['key' => 'B', 'text' => 'Bác sĩ', 'correct' => false],
            ['key' => 'C', 'text' => 'Giáo viên', 'correct' => false]
        ],
        'feedback' => 'Chuẩn xác! "학생" chính là âm Hán "Học sinh". Nhờ sự tương đồng này, người Việt học từ vựng tiếng Hàn cực kỳ nhanh!'
    ],
    [
        'id' => 'l4',
        'category' => 'language',
        'category_title' => 'Chữ cái & Ngữ âm Hangul',
        'question' => 'Trong tiếng Hàn, một âm tiết tối thiểu luôn được tạo thành từ cấu trúc nào?',
        'options' => [
            ['key' => 'A', 'text' => 'Ít nhất 1 Phụ âm + 1 Nguyên âm ghép thành khối vuông', 'correct' => true],
            ['key' => 'B', 'text' => 'Chỉ cần 1 phụ âm đứng một mình', 'correct' => false],
            ['key' => 'C', 'text' => 'Luôn phải có 3 ký tự trở lên', 'correct' => false]
        ],
        'feedback' => 'Chính xác! Tiếng Hàn ghép theo khối vuông (âm tiết), luôn gồm phụ âm đầu kết hợp với nguyên âm.'
    ],

    // Tier 3: Practical Situations & Goals
    [
        'id' => 's1',
        'category' => 'situation',
        'category_title' => 'Giao tiếp thực tế',
        'question' => 'Khi vào một quán ăn truyền thống tại Seoul, cách gọi cô chủ quán thân thiện và tự nhiên nhất là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Imo (이모) / Imo-nim (Dì ơi)', 'correct' => true],
            ['key' => 'B', 'text' => 'Ajusshi (아저씨 - Chú ơi)', 'correct' => false],
            ['key' => 'C', 'text' => 'Sajang-nim (사장님 - Giám đốc)', 'correct' => false]
        ],
        'feedback' => 'Rất chuẩn! "이모" (Dì ơi) là cách gọi cực kỳ gần gũi và được các cô chủ quán Hàn Quốc quý mến phục vụ chu đáo.'
    ],
    [
        'id' => 's2',
        'category' => 'situation',
        'category_title' => 'Giao tiếp thực tế',
        'question' => 'Khi bạn ra về và người kia ở lại, câu chào tạm biệt lịch sự nhất là gì?',
        'options' => [
            ['key' => 'A', 'text' => '안녕히 계세요 (An-nyeong-hi gye-se-yo - Ở lại bình an)', 'correct' => true],
            ['key' => 'B', 'text' => '안녕히 가세요 (An-nyeong-hi ga-se-yo - Đi về bình an)', 'correct' => false],
            ['key' => 'C', 'text' => '또 만나요 (Tto man-na-yo - Hẹn gặp lại)', 'correct' => false]
        ],
        'feedback' => 'Rất tinh tế! "계세요" là chúc người ở lại bình an, còn "가세요" là chúc người ra về đi đường bình an.'
    ],
    [
        'id' => 's3',
        'category' => 'situation',
        'category_title' => 'Giao tiếp thực tế',
        'question' => 'Khi nâng ly chúc mừng trước người lớn tuổi hoặc thầy cô giáo Hàn Quốc, nét văn hóa lịch sự chuẩn mực là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Xoay người sang một bên và dùng tay che ly khi uống', 'correct' => true],
            ['key' => 'B', 'text' => 'Nhìn thẳng vào mắt và uống hết một hơi', 'correct' => false],
            ['key' => 'C', 'text' => 'Nâng ly cao hơn hẳn ly của người lớn', 'correct' => false]
        ],
        'feedback' => 'Chính xác! Văn hóa kính ngữ của Hàn Quốc thể hiện qua cử chỉ xoay nhẹ người và dùng hai tay nâng ly tôn kính.'
    ],
    [
        'id' => 's4',
        'category' => 'situation',
        'category_title' => 'Giao tiếp thực tế',
        'question' => 'Mục tiêu quan trọng nhất bạn muốn đạt được khi học tiếng Hàn tại KIPA là gì?',
        'options' => [
            ['key' => 'A', 'text' => 'Đạt TOPIK du học trường TOP & săn học bổng', 'correct' => true],
            ['key' => 'B', 'text' => 'Giao tiếp phản xạ tự tin để đi làm / XKLD EPS', 'correct' => true],
            ['key' => 'C', 'text' => 'Thành thạo 4 kỹ năng từ con số 0 trong thời gian nhanh nhất', 'correct' => true]
        ],
        'feedback' => 'Tuyệt vời! Dù mục tiêu của bạn là gì, phương pháp kèm 1:1 tại KIPA sẽ đồng hành cùng bạn đến đích thành công.'
    ]
];

echo json_encode([
    'ok' => true,
    'total' => count($questions),
    'questions' => $questions
], JSON_UNESCAPED_UNICODE);
