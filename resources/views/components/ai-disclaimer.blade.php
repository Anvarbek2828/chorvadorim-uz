
@props(['type' => 'default'])



<div class="ai-disclaimer">

    <svg class="ai-disclaimer__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">

        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>

        <line x1="12" y1="9" x2="12" y2="13"/>

        <line x1="12" y1="17" x2="12.01" y2="17"/>

    </svg>

    <span class="ai-disclaimer__text">

        @if($type === 'image')

            Biz sizning rasmingiz asosida xulosa chiqaramiz, bu haqiqiy holatdan biroz farq qilishi mumkin — ya'ni AI xato qilishi mumkin. Iltimos, natijalarni tekshiring va muhim qarorlar uchun veterinar mutaxassisga murojaat qiling.

        @else

            Bu AI tomonidan berilgan javob bo'lib, xato qilishi mumkin. Iltimos, javoblarni tekshiring va muhim qarorlar uchun veterinar mutaxassisga murojaat qiling.

        @endif

    </span>

</div>



<style>

    .ai-disclaimer {

        display: flex;

        align-items: flex-start;

        gap: 8px;

        background-color: #fff8e6;

        border: 1px solid #f5d97a;

        border-radius: 8px;

        padding: 10px 14px;

        margin-top: 12px;

    }

    .ai-disclaimer__icon {

        flex-shrink: 0;

        width: 18px;

        height: 18px;

        color: #b8860b;

        margin-top: 1px;

    }

    .ai-disclaimer__text {

        font-size: 13px;

        line-height: 1.5;

        color: #7a6420;

    }

</style>

