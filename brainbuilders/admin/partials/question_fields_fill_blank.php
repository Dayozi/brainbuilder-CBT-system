<div class="form-group">
    <label for="correct_answer">Correct Answer *</label>
    <input type="text" id="correct_answer" name="correct_answer" 
           value="<?php echo isset($_POST['correct_answer']) ? htmlspecialchars($_POST['correct_answer']) : ''; ?>" required>
</div>