/**
 * 공통 API 함수
 */

const API_URL = 'api.php';

const api = {
    // 라벨러 로그인
    async login(key) {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('key', key);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 로그아웃
    async logout() {
        const formData = new FormData();
        formData.append('action', 'logout');
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 세션 확인
    async checkSession() {
        const response = await fetch(`${API_URL}?action=check_session`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 진행 상황 조회
    async getProgress() {
        const response = await fetch(`${API_URL}?action=get_progress`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 문서 세그먼트 조회
    async getSegments(docKey) {
        const response = await fetch(`${API_URL}?action=get_segments&doc_key=${encodeURIComponent(docKey)}`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 다음 미라벨링 세그먼트 조회
    async getNextSegment() {
        const response = await fetch(`${API_URL}?action=get_next_segment`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 라벨 저장
    async saveLabel(formData) {
        formData.append('action', 'save_label');
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 문서 목록 조회
    async getDocumentList() {
        const response = await fetch(`${API_URL}?action=get_document_list`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 관리자 로그인
    async adminLogin(key) {
        const formData = new FormData();
        formData.append('action', 'admin_login');
        formData.append('key', key);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 라벨러 목록 조회
    async getLabelers() {
        const response = await fetch(`${API_URL}?action=get_labelers`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 라벨러 추가
    async addLabeler(nickname) {
        const formData = new FormData();
        formData.append('action', 'add_labeler');
        formData.append('nickname', nickname);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 라벨러 삭제
    async deleteLabeler(labelerId) {
        const formData = new FormData();
        formData.append('action', 'delete_labeler');
        formData.append('labeler_id', labelerId);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 전체 진행 현황 조회
    async getAllProgress() {
        const response = await fetch(`${API_URL}?action=get_all_progress`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 결과 내보내기
    async exportResults() {
        const response = await fetch(`${API_URL}?action=export_results`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 공지사항 목록 조회
    async getAnnouncements() {
        const response = await fetch(`${API_URL}?action=get_announcements`, {
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 공지사항 추가 (관리자)
    async addAnnouncement(title, content) {
        const formData = new FormData();
        formData.append('action', 'add_announcement');
        formData.append('title', title);
        formData.append('content', content);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    },
    
    // 공지사항 삭제 (관리자)
    async deleteAnnouncement(announcementId) {
        const formData = new FormData();
        formData.append('action', 'delete_announcement');
        formData.append('announcement_id', announcementId);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        return response.json();
    }
};
