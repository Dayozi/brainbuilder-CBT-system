<div class="option-group">
    <h3>Multiple Choice Options</h3>
    <p class="instruction">Select the radio button next to the correct answer</p>
    
    <?php
    $options = ['A', 'B', 'C', 'D'];
    foreach ($options as $option) {
        $optionValue = strtolower($option);
        $optionName = "option_$optionValue";
    ?>
    <div class="option-row">
        <input type="radio" 
               name="correct_option" 
               value="<?php echo $option; ?>" 
               id="correct_option_<?php echo $optionValue; ?>"
               <?php if (isset($_POST['correct_option']) && $_POST['correct_option'] == $option) echo 'checked'; ?> 
               required>
        
        <span class="option-label"><?php echo $option; ?></span>
        
        <input type="text" 
               name="<?php echo $optionName; ?>" 
               placeholder="Option <?php echo $option; ?>" 
               value="<?php echo isset($_POST[$optionName]) ? htmlspecialchars($_POST[$optionName]) : ''; ?>" 
               required>
    </div>
    <?php } ?>
</div>