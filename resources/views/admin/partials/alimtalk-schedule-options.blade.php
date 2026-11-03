<label class="dispatch-field">발송 방식
    <select name="delivery_mode" class="form-select js-delivery-mode">
        <option value="scheduled">예약 발송</option>
        <option value="immediate">즉시 발송</option>
    </select>
</label>
<label class="dispatch-field dispatch-optional js-schedule-time">예약 날짜·시간 (한국 시간)
    <input type="datetime-local" name="scheduled_at" class="form-control" required>
    <span class="text-muted">현재보다 5분 이후로 지정해주세요.</span>
</label>
