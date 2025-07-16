<div class="true-false-options">
    <div class="true-false-option">
        <input type="radio" name="true_false_answer" value="True" id="true_answer"
               <?php if (isset($_POST['true_false_answer']) && $_POST['true_false_answer'] == 'True') echo 'checked'; ?> required>
        <label for="true_answer">True</label>
    </div>
    <div class="true-false-option">
        <input type="radio" name="true_false_answer" value="False" id="false_answer"
               <?php if (isset($_POST['true_false_answer']) && $_POST['true_false_answer'] == 'False') echo 'checked'; ?>>
        <label for="false_answer">False</label>
    </div>
</div>