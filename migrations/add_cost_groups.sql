-- Bảng nhóm chi phí
CREATE TABLE IF NOT EXISTS cost_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gán nhóm cho dòng báo giá
ALTER TABLE quotation_items ADD COLUMN IF NOT EXISTS cost_group_id INT NULL DEFAULT NULL;

-- Insert nhóm mẫu
INSERT INTO cost_groups (name, sort_order) VALUES
('Phí hải quan', 1),
('Phí vận chuyển', 2),
('Phí lưu kho', 3),
('Phí khác', 4);
